<div wire:ignore class="filePondContainer"
     wire:model="{{ $attributes['wire:model'] }}"
     delete-event="{{ $attributes['delete-event'] }}"
     allow-remove="{{ $attributes['allow-remove'] }}"
     file-path="{{ $attributes['file-path'] }}"
     is-multiple="{{ $attributes['is-multiple'] }}"
     data-uploaded-files="{{ json_encode(@$images) }}"
>
    <input type="file" class="fileInput" @if($attributes['is-multiple'] == "true") multiple @endif />
</div>

@script
<script>


    FilePond.registerPlugin(FilePondPluginFileValidateType);
    FilePond.registerPlugin(FilePondPluginImagePreview);

    function initializeFilepond(filePondContainers) {
        filePondContainers.forEach((container, index) => {

            const fileInput = container.querySelector('.fileInput');
            const uploadedFilesData = container.getAttribute('data-uploaded-files');
            const uploadedFiles = JSON.parse(uploadedFilesData || '[]');
            const wireModel = container.getAttribute('wire:model');
            const allowRemove = container.getAttribute('allow-remove');
            const uploadedFile = container.getAttribute('file-path');
            const isMultiple = container.getAttribute('is-multiple');

            let deleteEvent = container.getAttribute('delete-event');
            let params = {};

            if (deleteEvent.includes('(') && deleteEvent.includes(')')) {
                // Extract the event name
                let eventName = deleteEvent.split('(')[0];

                // Extract the parameters inside the parentheses
                let paramsStr = deleteEvent.match(/\(([^)]+)\)/)[1];

                // Assuming the parameter is always an id for this case
                params = { key: parseInt(paramsStr) };

                deleteEvent = eventName
            }

            let options = {};
            if (allowRemove == "false") {
                options.allowRemove = false;
            }

            const pond = FilePond.create(fileInput, options);

            let imagesArray = [];

            let files = [];

            if (isMultiple === "false") {
                if (uploadedFile) {
                    files.push({
                        source: uploadedFile,
                        options: {
                            type: 'local',
                            load: true
                        }
                    })
                }
            } else {
                if (uploadedFiles.length > 0) {
                    for (let i = 0; i < uploadedFiles.length; i++) {
                        files.push({
                            source: uploadedFiles[i],
                            options: {
                                type: 'local',
                                load: true
                            }
                        })
                    }
                }
            }

            pond.setOptions({
                server: {
                    load: (source, load, error, progress, abort, headers) => {
                        var myRequest = new Request(source);
                        fetch(myRequest).then((res) => {
                            return res.blob();
                        }).then(load);
                    },

                    process: (fieldName, file, metadata, load, error, progress, abort, transfer, options) => {
                        if (isMultiple == "true") {
                            imagesArray.push(file)
                            $wire.uploadMultiple(wireModel, imagesArray, load, error, progress)
                        } else {
                            $wire.upload(wireModel, file, load, error, progress)
                        }
                    },


                    revert: (filename, load) => {
                        if (filename) {
                            $wire.dispatch(deleteEvent);
                        }
                        // Revert logic...
                        $wire.removeUpload(wireModel, filename, load)
                    },
                    remove: (filename, load, error) => {
                        if (isMultiple == "true") {
                            params = {
                                filename
                            };
                        }
                        $wire.dispatch(deleteEvent, params);

                        load()
                    },
                },
                files: files,
            });

        });
    }

    const filePondContainers = document.querySelectorAll('.filePondContainer');
    initializeFilepond(filePondContainers)

    Livewire.hook('morph.added', ({ el }) => {
        let newFilepondContainers = el.querySelectorAll('.filePondContainer');
        initializeFilepond(newFilepondContainers)
    })


</script>
@endscript