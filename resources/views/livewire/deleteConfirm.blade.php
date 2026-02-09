<script>
$(document).on('click', '.delete-button', function () {
    let id = $(this).data('id');
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then((willDelete) => {
        if (willDelete.isConfirmed) {
            $wire.dispatch("delete", { id: id });
        }
    });
});
</script>
