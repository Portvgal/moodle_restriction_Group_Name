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

namespace availability_groupname;

/**
 * Unit tests for the group name condition.
 *
 * @package availability_groupname
 * @copyright 2026 Portvgal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class condition_test extends \advanced_testcase {
    public function setUp(): void {
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
        parent::setUp();
    }

    public function test_matching_rules(): void {
        global $CFG, $USER;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_and_enrol($course);
        $nomatch = $generator->create_and_enrol($course);
        $info = new \core_availability\mock_info($course, $user->id);

        $group1 = $generator->create_group([
            'courseid' => $course->id,
            'name' => '25_32_CHC3024_FR_Group_1',
        ]);
        $group2 = $generator->create_group([
            'courseid' => $course->id,
            'name' => 'Another_BSB30120_Group',
        ]);
        groups_add_member($group1, $user);
        groups_add_member($group2, $user);
        $info = new \core_availability\mock_info($course, $user->id);

        $cond = new condition((object)['op' => condition::OP_EXACT, 'v' => 'another_bsb30120_group']);
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(false, $info, true, $nomatch->id));

        $cond = new condition((object)['op' => condition::OP_CONTAINS, 'v' => 'chc3024']);
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));

        $cond = new condition((object)['op' => condition::OP_STARTS_WITH, 'v' => '25_32']);
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(false, $info, true, $nomatch->id));
        $this->assertTrue($cond->is_available(true, $info, true, $nomatch->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        $this->setAdminUser();
        $this->assertTrue($cond->is_available(false, $info, true, $USER->id));
        $this->assertTrue($cond->is_available(true, $info, true, $USER->id));
    }

    public function test_constructor_validation(): void {
        $this->expectException(\coding_exception::class);
        new condition((object)['op' => 'endswith', 'v' => '25_32']);
    }

    public function test_constructor_rejects_blank_values(): void {
        $this->expectException(\coding_exception::class);
        new condition((object)['op' => condition::OP_EXACT, 'v' => '   ']);
    }

    public function test_save_trims_and_returns_stable_json(): void {
        $cond = new condition((object)['op' => condition::OP_STARTS_WITH, 'v' => ' 26_77 ']);
        $this->assertEquals((object)[
            'type' => 'groupname',
            'op' => condition::OP_STARTS_WITH,
            'v' => '26_77',
        ], $cond->save());
    }

    public function test_filter_users_and_sql_are_equivalent(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');

        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, $roleids['editingteacher']);
        $allusers = [$teacher->id => $teacher];

        $students = [];
        for ($i = 0; $i < 3; $i++) {
            $student = $generator->create_user();
            $students[$i] = $student;
            $generator->enrol_user($student->id, $course->id, $roleids['student']);
            $allusers[$student->id] = $student;
        }

        $matchinggroup = $generator->create_group([
            'courseid' => $course->id,
            'name' => '26_32_Diploma_Group',
        ]);
        $othergroup = $generator->create_group([
            'courseid' => $course->id,
            'name' => '99_99_Other_Group',
        ]);
        groups_add_member($matchinggroup, $students[1]);
        groups_add_member($othergroup, $students[2]);

        $info = new \core_availability\mock_info($course);
        $checker = new \core_availability\capability_checker($info->get_context());
        $cond = new condition((object)['op' => condition::OP_STARTS_WITH, 'v' => '26_32']);

        $expected = [$teacher->id, $students[1]->id];
        $this->assert_user_filter_matches($expected, $cond, false, $allusers, $info, $checker);

        $expected = [$teacher->id, $students[0]->id, $students[2]->id];
        $this->assert_user_filter_matches($expected, $cond, true, $allusers, $info, $checker);
    }

    /**
     * Checks filter_user_list and get_user_list_sql against the same expected users.
     *
     * @param int[] $expected Expected user ids.
     * @param condition $cond Condition.
     * @param bool $not Whether the condition is negated.
     * @param array $allusers Users to filter.
     * @param \core_availability\mock_info $info Availability info.
     * @param \core_availability\capability_checker $checker Capability checker.
     */
    protected function assert_user_filter_matches(
        array $expected,
        condition $cond,
        bool $not,
        array $allusers,
        \core_availability\mock_info $info,
        \core_availability\capability_checker $checker
    ): void {
        global $DB;

        sort($expected);

        $result = array_keys($cond->filter_user_list($allusers, $not, $info, $checker));
        sort($result);
        $this->assertEquals($expected, $result);

        [$sql, $params] = $cond->get_user_list_sql($not, $info, true);
        $result = $DB->get_fieldset_sql($sql, $params);
        sort($result);
        $this->assertEquals($expected, $result);
    }
}
