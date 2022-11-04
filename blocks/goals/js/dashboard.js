debugger;
$(document).ready(function() {
debugger;
    $('.updategoal').on('click', function() {
        debugger;
        $('#exampleModal').modal('show');
    });

    $('.goaltable').DataTable({
            responsive: true,
            "columnDefs": [
                {"visible": true, "searchable": false, "targets": 0},
                {"visible": true, "searchable": true, "targets": 1},
                {"visible": true, "searchable": true, "targets": 2},
                {"visible": false, "searchable": true, "targets": 3},
                {"visible": false, "searchable": true, "targets": 4},
                {"visible": false, "searchable": true, "targets": 5},
                {"visible": false, "searchable": true, "targets": 6},
                {"visible": false, "searchable": true, "targets": 7},
                {"visible": true, "searchable": true, "targets": 8},
                {"visible": true, "searchable": true, "targets": 9},
            ]
        }
    );


});