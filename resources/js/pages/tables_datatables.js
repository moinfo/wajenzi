/*
 *  Document   : tables_datatables.js
 *  Author     : pixelcave
 *  Description: Custom JS code used in Plugin Init Example Page
 */

// DataTables, for more examples you can check out https://www.datatables.net/
class pageTablesDatatables {
    /*
     * Init DataTables functionality
     *
     */
    static initDataTables() {
        // Override a few DataTable defaults
        jQuery.extend( jQuery.fn.dataTable.ext.classes, {
            sWrapper: "dataTables_wrapper dt-bootstrap4"
        });
        // $.extend($.fn.dataTable.defaults, {
        //     dom: 'Bfrtip'
        // });
        // Init full DataTable
        jQuery('.js-dataTable-full').dataTable({
            dom: 'Bfrtip',
            buttons: [ 'print', 'excel', 'pdf' ],
            pageLength: 200,
            lengthMenu: [[30, 50, 90], [30, 50, 90]],
            autoWidth: false


        });
    }

    /*
     * Init functionality
     *
     */
    static init() {
        this.initDataTables();
    }
}

// Initialize when page loads
jQuery(() => { pageTablesDatatables.init(); });
