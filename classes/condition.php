<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Condition main class.
 *
 * @package availability_groupname
 * @copyright 2026 Portvgal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_groupname;

/**
 * Condition main class.
 *
 * @package availability_groupname
 * @copyright 2026 Portvgal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var string Operator: group name equals value. */
    public const OP_EXACT = 'exact';

    /** @var string Operator: group name contains value. */
    public const OP_CONTAINS = 'contains';

    /** @var string Operator: group name starts with value. */
    public const OP_STARTS_WITH = 'startswith';

    /** @var string[] Supported operators. */
    protected const OPERATORS = [
        self::OP_EXACT,
        self::OP_CONTAINS,
        self::OP_STARTS_WITH,
    ];

    /** @var string Operator type. */
    protected $operator;

    /** @var string Required group name value. */
    protected $value;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode.
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        if (
            isset($structure->op) && is_string($structure->op) &&
                in_array($structure->op, self::OPERATORS, true)
        ) {
            $this->operator = $structure->op;
        } else {
            throw new \coding_exception('Missing or invalid ->op for groupname condition');
        }

        if (!isset($structure->v) || !is_string($structure->v)) {
            throw new \coding_exception('Missing or invalid ->v for groupname condition');
        }

        $this->value = trim($structure->v);
        if ($this->value === '') {
            throw new \coding_exception('Blank ->v for groupname condition');
        }
    }

    /**
     * Saves the condition settings as an availability JSON object.
     *
     * @return \stdClass Availability JSON object.
     */
    public function save() {
        return (object)[
            'type' => 'groupname',
            'op' => $this->operator,
            'v' => $this->value,
        ];
    }

    /**
     * Checks whether the condition allows access for a user.
     *
     * @param bool $not True if the condition is negated.
     * @param \core_availability\info $info Availability info.
     * @param bool $grabthelot True if additional data may be grabbed.
     * @param int $userid User id.
     * @return bool True if available.
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;

        $course = $info->get_course();
        $context = \context_course::instance($course->id);
        $allow = true;

        if (!has_capability('moodle/site:accessallgroups', $context, $userid)) {
            $groups = $info->get_groups(0, $userid);
            if (!$groups) {
                $allow = false;
            } else {
                [$insql, $params] = $DB->get_in_or_equal($groups, SQL_PARAMS_NAMED);
                $params['courseid'] = $course->id;
                $names = $DB->get_fieldset_select(
                    'groups',
                    'name',
                    "courseid = :courseid AND id $insql",
                    $params
                );
                $allow = $this->matches_any_group_name($names);
            }

            // Match core availability_group: NOT applies before accessallgroups.
            if ($not) {
                $allow = !$allow;
            }
        }

        return $allow;
    }

    /**
     * Gets a human-readable description of the condition.
     *
     * @param bool $full True if full information should be included.
     * @param bool $not True if the condition is negated.
     * @param \core_availability\info $info Availability info.
     * @return string Description.
     */
    public function get_description($full, $not, \core_availability\info $info) {
        $opname = $this->operator;
        if ($not) {
            switch ($this->operator) {
                case self::OP_EXACT:
                    $opname = 'notexact';
                    break;
                case self::OP_CONTAINS:
                    $opname = 'notcontains';
                    break;
                case self::OP_STARTS_WITH:
                    $opname = 'notstartswith';
                    break;
                default:
                    throw new \coding_exception('Unexpected operator: ' . $this->operator);
            }
        }
        return get_string('requires_' . $opname, 'availability_groupname', s($this->value));
    }

    /**
     * Gets a debug string for this condition.
     *
     * @return string Debug string.
     */
    protected function get_debug_string() {
        return $this->operator . ' ' . $this->value;
    }

    /**
     * Returns whether this condition applies to user lists.
     *
     * @return bool True if this condition filters user lists.
     */
    public function is_applied_to_user_lists() {
        return true;
    }

    /**
     * Filters a user list according to this condition.
     *
     * @param array $users Users to filter.
     * @param bool $not True if the condition is negated.
     * @param \core_availability\info $info Availability info.
     * @param \core_availability\capability_checker $checker Capability checker.
     * @return array Filtered users.
     */
    public function filter_user_list(
        array $users,
        $not,
        \core_availability\info $info,
        \core_availability\capability_checker $checker
    ) {
        global $DB;

        if (!$users) {
            return $users;
        }

        $course = $info->get_course();
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($users), SQL_PARAMS_NAMED);
        $params = $inparams;
        $namesql = $this->get_group_name_condition_sql('g.name', $params);
        $params['courseid'] = $course->id;

        $matchingusers = $DB->get_records_sql("
                SELECT DISTINCT gm.userid
                  FROM {groups} g
                  JOIN {groups_members} gm ON gm.groupid = g.id
                 WHERE g.courseid = :courseid
                       AND gm.userid $insql
                       AND $namesql", $params);
        $aagusers = $checker->get_users_by_capability('moodle/site:accessallgroups');

        $result = [];
        foreach ($users as $id => $user) {
            if (array_key_exists($id, $aagusers)) {
                $result[$id] = $user;
                continue;
            }
            $allow = array_key_exists($id, $matchingusers);
            if ($not) {
                $allow = !$allow;
            }
            if ($allow) {
                $result[$id] = $user;
            }
        }
        return $result;
    }

    /**
     * Gets SQL that returns users matching this condition.
     *
     * @param bool $not True if the condition is negated.
     * @param \core_availability\info $info Availability info.
     * @param bool $onlyactive True to include only active enrolments.
     * @return array SQL and parameters.
     */
    public function get_user_list_sql($not, \core_availability\info $info, $onlyactive) {
        [$aagsql, $aagparams] = get_enrolled_sql(
            $info->get_context(),
            'moodle/site:accessallgroups',
            0,
            $onlyactive
        );
        [$enrolsql, $enrolparams] = get_enrolled_sql($info->get_context(), '', 0, $onlyactive);

        $params = [];
        $courseparam = self::unique_sql_parameter($params, $info->get_course()->id);
        $namesql = $this->get_group_name_condition_sql('g.name', $params);
        $condition = $not ? 'NOT' : '';
        $matchsql = "SELECT 1
                       FROM {groups_members} gm
                       JOIN {groups} g ON g.id = gm.groupid
                      WHERE gm.userid = userids.id
                            AND g.courseid = $courseparam
                            AND $namesql";

        $sql = "SELECT userids.id
                  FROM ($enrolsql) userids
                 WHERE (userids.id IN ($aagsql)) OR $condition EXISTS ($matchsql)";

        return [$sql, array_merge($enrolparams, $aagparams, $params)];
    }

    /**
     * Returns a JSON object for unit tests.
     *
     * @param string $operator Operator name.
     * @param string $value Group name value.
     * @return \stdClass
     */
    public static function get_json($operator, $value) {
        return (object)[
            'type' => 'groupname',
            'op' => $operator,
            'v' => $value,
        ];
    }

    /**
     * Checks whether any group name matches this condition.
     *
     * @param string[] $names Group names.
     * @return bool
     */
    protected function matches_any_group_name(array $names): bool {
        foreach ($names as $name) {
            if (self::matches_group_name($this->operator, $name, $this->value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks a single group name against an operator and value.
     *
     * @param string $operator Operator.
     * @param string $name Group name.
     * @param string $value Required value.
     * @return bool
     */
    protected static function matches_group_name(string $operator, string $name, string $value): bool {
        $name = \core_text::strtolower($name);
        $value = \core_text::strtolower($value);

        switch ($operator) {
            case self::OP_EXACT:
                return $name === $value;
            case self::OP_CONTAINS:
                return strpos($name, $value) !== false;
            case self::OP_STARTS_WITH:
                return substr($name, 0, strlen($value)) === $value;
            default:
                throw new \coding_exception('Unexpected operator: ' . $operator);
        }
    }

    /**
     * Gets SQL to match a group name against this condition.
     *
     * @param string $field Group name field SQL.
     * @param array $params Parameters array to add to.
     * @return string SQL fragment.
     */
    protected function get_group_name_condition_sql(string $field, array &$params): string {
        global $DB;

        switch ($this->operator) {
            case self::OP_EXACT:
                $sql = $DB->sql_equal(
                    $field,
                    self::unique_sql_parameter($params, $this->value),
                    false
                );
                break;
            case self::OP_CONTAINS:
                $sql = $DB->sql_like(
                    $field,
                    self::unique_sql_parameter($params, '%' . $DB->sql_like_escape($this->value) . '%'),
                    false
                );
                break;
            case self::OP_STARTS_WITH:
                $sql = $DB->sql_like(
                    $field,
                    self::unique_sql_parameter($params, $DB->sql_like_escape($this->value) . '%'),
                    false
                );
                break;
            default:
                throw new \coding_exception('Unexpected operator: ' . $this->operator);
        }
        return $sql;
    }
}
