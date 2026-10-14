(function ($) {
    'use strict';

    var language = window.datatablesLanguage || {};

    function hideLegacyPagination($table) {
        var $card = $table.closest('.card, .box');
        $card.find('.card-footer').hide();
        $card.find('.card-tools form, .list-toolbar form').each(function () {
            var $form = $(this);
            if ($form.find('input[name="s"]').length) {
                $form.closest('.card-tools, .col-lg-5, .col-md-4').hide();
            }
        });
        $card.find('.btn-group .dropdown-menu').parent().filter(function () {
            return $(this).find('a[href*="per_page"]').length > 0;
        }).hide();
    }

    function getServerSideColumns($table) {
        var columns = [];

        $table.find('thead th').each(function () {
            var $th = $(this);
            var data = $th.data('data') || $th.data('column');

            if (!data) {
                return;
            }

            columns.push({
                data: data,
                name: $th.data('name') || data,
                orderable: $th.data('orderable') !== false && $th.data('orderable') !== 'false',
                searchable: $th.data('searchable') !== false && $th.data('searchable') !== 'false'
            });
        });

        return columns;
    }

    function initServerSideTable($table) {
        var ajaxUrl = $table.data('ajax');
        if (!ajaxUrl) {
            return false;
        }

        var columns = getServerSideColumns($table);
        if (!columns.length) {
            return false;
        }

        var order = [[0, 'desc']];
        if ($table.data('order')) {
            try {
                order = typeof $table.data('order') === 'string'
                    ? JSON.parse($table.data('order'))
                    : $table.data('order');
            } catch (e) {
                order = [[0, 'desc']];
            }
        }

        $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: ajaxUrl,
            columns: columns,
            order: order,
            language: language,
            autoWidth: false,
            responsive: false,
            drawCallback: function () {
                if (window.bindDatatableConfirmHandlers) {
                    window.bindDatatableConfirmHandlers();
                }
            }
        });

        hideLegacyPagination($table);
        return true;
    }

    function initClientSideTable($table) {
        if ($table.find('tbody tr').length === 0) {
            return false;
        }

        var nonEmptyRows = $table.find('tbody tr').filter(function () {
            return $(this).find('td').length > 0 && !$(this).hasClass('dataTables_empty');
        });

        if (nonEmptyRows.length === 0) {
            return false;
        }

        var columnCount = $table.find('thead th').length;
        if (columnCount === 0) {
            return false;
        }

        var lastColumnIndex = columnCount - 1;
        var orderableTargets = [];
        $table.find('thead th').each(function (index) {
            if ($(this).data('orderable') === false || $(this).data('orderable') === 'false') {
                orderableTargets.push(index);
            }
        });

        if (orderableTargets.indexOf(lastColumnIndex) === -1) {
            orderableTargets.push(lastColumnIndex);
        }

        $table.DataTable({
            order: [],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            language: language,
            autoWidth: false,
            responsive: false,
            columnDefs: [
                {orderable: false, targets: orderableTargets}
            ],
            drawCallback: function () {
                if (window.bindDatatableConfirmHandlers) {
                    window.bindDatatableConfirmHandlers();
                }
            }
        });

        hideLegacyPagination($table);
        return true;
    }

    function initDataTables() {
        $('table.table, table.data-table').not('.no-datatable').each(function () {
            if ($.fn.DataTable.isDataTable(this)) {
                return;
            }

            var $table = $(this);

            if (initServerSideTable($table)) {
                return;
            }

            initClientSideTable($table);
        });
    }

    $(document).ready(function () {
        initDataTables();
    });

    window.initDataTables = initDataTables;
})(jQuery);
