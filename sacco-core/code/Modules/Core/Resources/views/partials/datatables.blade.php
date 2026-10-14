<script>
    window.datatablesLanguage = {
        lengthMenu: @json(trans('general.lengthMenu')),
        zeroRecords: @json(trans('general.zeroRecords')),
        info: @json(trans('general.info')),
        infoEmpty: @json(trans('general.infoEmpty')),
        search: @json(trans('general.search')),
        infoFiltered: @json(trans('general.infoFiltered')),
        paginate: {
            first: @json(trans('general.first')),
            last: @json(trans('general.last')),
            next: @json(trans('general.next')),
            previous: @json(trans('general.previous'))
        }
    };

    window.bindDatatableConfirmHandlers = function () {
        $('.confirm').off('click.datatables').on('click.datatables', function (e) {
            e.preventDefault();
            var href = $(this).attr('href');
            Swal.fire({
                title: @json(trans_choice('core::general.are_you_sure', 1)),
                text: '',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: @json($brand_primary ?? '#0d5c3d'),
                cancelButtonColor: '#d33',
                confirmButtonText: @json(trans_choice('core::general.ok', 1)),
                cancelButtonText: @json(trans_choice('core::general.cancel', 1))
            }).then(function (result) {
                if (result.value) {
                    window.location = href;
                }
            });
        });
    };
</script>
