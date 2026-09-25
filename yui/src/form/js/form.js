/**
 * JavaScript for form editing group name conditions.
 *
 * @module moodle-availability_groupname-form
 */
M.availability_groupname = M.availability_groupname || {};

/**
 * @class M.availability_groupname.form
 * @extends M.core_availability.plugin
 */
M.availability_groupname.form = Y.Object(M.core_availability.plugin);

M.availability_groupname.form.getNode = function(json) {
    var html = '<span class="availability-groupname"><label><span class="pe-3">' +
            M.util.get_string('conditiontitle', 'availability_groupname') + '</span> ' +
            '<select name="op" title="' + M.util.get_string('label_operator', 'availability_groupname') + '"' +
                    ' class="form-select">' +
            '<option value="exact">' + M.util.get_string('op_exact', 'availability_groupname') + '</option>' +
            '<option value="contains">' + M.util.get_string('op_contains', 'availability_groupname') + '</option>' +
            '<option value="startswith">' + M.util.get_string('op_startswith', 'availability_groupname') + '</option>' +
            '</select></label> <label><span class="accesshide">' +
            M.util.get_string('label_value', 'availability_groupname') +
            '</span><input name="value" type="text" class="form-control" style="width: 14em" title="' +
            M.util.get_string('label_value', 'availability_groupname') + '"/></label></span>';
    var node = Y.Node.create('<span class="d-flex flex-wrap align-items-center">' + html + '</span>');

    if (json.op !== undefined && node.one('select[name=op] > option[value=' + json.op + ']')) {
        node.one('select[name=op]').set('value', json.op);
    }
    if (json.v !== undefined) {
        node.one('input[name=value]').set('value', json.v);
    }

    if (!M.availability_groupname.form.addedEvents) {
        M.availability_groupname.form.addedEvents = true;
        var root = Y.one('.availability-field');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_groupname select');
        root.delegate('change', function() {
            M.core_availability.form.update();
        }, '.availability_groupname input[name=value]');
    }

    return node;
};

M.availability_groupname.form.fillValue = function(value, node) {
    value.op = node.one('select[name=op]').get('value');
    value.v = node.one('input[name=value]').get('value');
};

M.availability_groupname.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    if (value.v === undefined || /^\s*$/.test(value.v)) {
        errors.push('availability_groupname:error_setvalue');
    }
};
