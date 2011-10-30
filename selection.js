$(document).ready(function () {
    var potentials = "#from_users";
    var mailed = "#mail_users";

    var selectors = [potentials, mailed];

    function clear_selections() {
        $(selectors).each(function(index, selector) {
            $(selector).children(":selected").attr("selected", false);
        });
    }

    function quickmail_changer() {
        var role = $("#roles").val();

        // Give me something clean to work with
        clear_selections();

        $("#groups").children(":selected").each(function(index, group) {
            $(selectors).each(function(index, selector) {

                // Select those that match group
                $(selector).children("*").each(function(index, option) {
                    var values = $(option).val().split(' ');
                    var roles = values[2].split(',');
                    var groups = values[1].split(',');

                    var in_list = function(obj, list) {
                        return $(list).filter(function() {
                            return this == obj;
                        }).length > 0;
                    };

                    // Have the right role and in the right group
                    //var selected = (in_list(role, roles) && in_list($(group).val(), groups)) ? true : false;
                    var selected = true; 
                    if(in_list(role, roles) && in_list($(group).val(), groups)) {
                        $(option).attr('selected', selected);
                    }
                });
            });
        });
    } 
    
    function move(from, to, filter) {
        return function() {
            // Copy
            $(from).children(filter).appendTo(to);
            // Remove
            $(from).children(filter).remove(); 
        };
    }
  
    $("#groups").change(quickmail_changer);
    $("#roles").change(quickmail_changer);

    $("#add_button").click(move(potentials, mailed, ':selected'));
    $("#add_all").click(move(potentials, mailed, '*'));
    $("#remove_button").click(move(mailed, potentials, ':selected'));
    $("#remove_all").click(move(mailed, potentials, '*'));

    // On submit, load the hidden input with all the 
    $("#mform1").submit(function() {
        var ids = $(mailed).children("*").map(function(index, elem) {
            return $(elem).val().split(' ')[0];
        }).get().join(',');

        if(ids == '') return false;

        $("input[name=mailto]").val(ids);

        return true;
    });
});
