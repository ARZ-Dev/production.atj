<script>
if (!window._lwDeleteHandlerBound) {
    window._lwDeleteHandlerBound = true;

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.delete-button');
        if (!btn) return;

        var id = btn.dataset.id;

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then(function (result) {
            if (result.isConfirmed) {
                Livewire.dispatch('delete', { id: id });
            }
        });
    });
}
</script>
