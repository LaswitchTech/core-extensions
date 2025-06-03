//
//   Core Framework - Script file
//
//   @license    MIT (https://mit-license.org/)
//   @author     Louis Ouellet <louis@laswitchtech.com>
//

const ExtensionsModalDev = function(extension, row){
    builder.Component(
        "modal",
        null,
        {
            onEnter: false,
            destroy: true,
            icon: "clipboard2-pulse",
            title: builder.Locale.get("Metadata"),
            cancel: false,
            submit: true,
            size: "xl",
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // Submit the form
                        element.form.submit();
                    }, 300);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Styling
            component.header.addClass('text-bg-indigo');
            component.footer.submit.addClass('btn-indigo').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            }).text(builder.Locale.get('Save Metadata'));
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-code-slash me-1').prependTo(component.footer.submit);

            // Form
            component.form = builder.Component(
                'form',
                component.body,
                {
                    class:{
                        form: 'row row-cols-3 g-3',
                        field: 'col',
                    },
                    callback:{
                        submit: function(form){

                            // AJAX Request
                            $.ajax({
                                url: '/endpoint.php/extensions/meta?type='+extension.type+'&base='+extension.base,
                                headers: {'X-CSRF-Authorization': CSRF_KEY},
                                type: 'POST',dataType: 'json',
                                data: {meta: form.val()},
                                success: function(response){

                                    // Update CSRF
                                    CSRF_KEY = response.CSRF.key;
                                    CSRF_TOKEN = response.CSRF.token;

                                    // Loop through the tables
                                    for(const [key, value] of Object.entries(form.val())){
                                        switch(key){
                                            default:
                                                row.find('[data-key="'+key+'"]').text(value);
                                                break;
                                        }
                                    }

                                    // Close the modal
                                    modal.hide();
                                }
                            });
                        },
                    },
                },
                function(form,component){

                    // Loop through the tables
                    for(const [key, value] of Object.entries(extension)){

                        // Check if key is in array
                        if(builder.Helper.inArray(key,["type","base","current","latest","installed","published","initialized"])){
                            continue;
                        }

                        // Create a select
                        form.add(
                            {
                                name: key,
                                label: builder.Locale.get(key.charAt(0).toUpperCase() + key.slice(1)),
                                icon: 'columns',
                                type: key === 'description' ? 'textarea' : 'text',
                                modal: componentModal,
                            },
                            function(input){

                                // Styling
                                if(builder.Helper.inArray(key,["description","repository","download","tracker","support","picture"])){
                                    input.addClass('col-12');
                                }
                                if(builder.Helper.inArray(key,["branch","token"])){
                                    input.addClass('col-6');
                                }

                                // Set default value
                                input.val(value);
                            },
                        );
                    }

                    // Open the modal
                    modal.show();
                },
            );
        }
    );
}
const ExtensionsModalPublish = function(extension){
    builder.Component(
        "modal",
        null,
        {
            onEnter: false,
            destroy: true,
            icon: "check-lg",
            title: builder.Locale.get("Publish Extension"),
            body: builder.Locale.get("This will publish the extension to the extensions feed. This will allow other users to install it."),
            cancel: false,
            submit: true,
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // AJAX Request
                        $.ajax({
                            url: '/endpoint.php/extensions/publish?type='+extension.type+'&base='+extension.base,
                            type: 'GET',dataType: 'json',
                            success: function(response){

                                // Close the modal
                                modal.hide();
                            }
                        });
                    }, 300);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Styling
            component.header.addClass('text-bg-blue');
            component.footer.submit.addClass('btn-blue').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            }).text(builder.Locale.get('Publish Extension'));
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-check-lg me-1').prependTo(component.footer.submit);

            // Open the modal
            modal.show();
        }
    );
}
const ExtensionsModalUnpublish = function(extension){
    builder.Component(
        "modal",
        null,
        {
            onEnter: false,
            destroy: true,
            icon: "x-lg",
            title: builder.Locale.get("Unpublish Extension"),
            body: builder.Locale.get("This will unpublish the extension from the extensions feed. This will prevent other users from installing it."),
            cancel: false,
            submit: true,
            callback: {
                submit: function(element,modal){

                    // Create a spinner animate-rotate
                    var spinner = $(document.createElement('div')).attr({
                        "class": "animate-rotate rounded-circle border border-secondary border-4 d-none",
                        "style": "width: 96px; height: 96px; border-top-color: var(--bs-primary)!important;",
                    }).appendTo(element);

                    // Hide the dialog
                    element.dialog.addClass('opacity-0');

                    // Setup a spinner while waiting for the modal to be submitted
                    setTimeout(() => {

                        // Hide the dialog
                        element.dialog.hide();

                        // Add flex to the modal
                        element.addClass('d-flex align-items-center justify-content-center');

                        // Show the spinner
                        spinner.removeClass('d-none');

                        // AJAX Request
                        $.ajax({
                            url: '/endpoint.php/extensions/unpublish?type='+extension.type+'&base='+extension.base,
                            type: 'GET',dataType: 'json',
                            success: function(response){

                                // Close the modal
                                modal.hide();
                            }
                        });
                    }, 300);
                },
            },
        },
        function(modal,component){

            // Save the component
            const componentModal = component;

            // Styling
            component.header.addClass('text-bg-danger');
            component.footer.submit.addClass('btn-danger').removeClass('btn-link').attr({
                "style": "border-bottom-right-radius: var(--bs-modal-inner-border-radius) !important;border-bottom-left-radius: var(--bs-modal-inner-border-radius) !important;",
            }).text(builder.Locale.get('Unpublish Extension'));
            component.footer.submit.icon = $(document.createElement('i')).addClass('bi bi-x-lg me-1').prependTo(component.footer.submit);

            // Open the modal
            modal.show();
        }
    );
}
const ExtensionsFeed = function(container, extensions){

    // Retrieve the first key of the extensions
    const first = Object.keys(extensions)[0];

    // Handle success extensions
    for(const [base, extension] of Object.entries(extensions)){

        // Create a row
        const row = $(document.createElement('div')).addClass('d-flex flex-row align-items-center').appendTo(container);

        // Check if the row is the first one
        if(base !== first){
            row.addClass('border-top border-1 border-secondary mt-3 pt-3');
        }

        // Picture
        row.picture = $(document.createElement('div')).attr({
            "class": 'flex-shrink-0 rounded-circle border border-3 border-light',
            "style": "width: 128px; height: 128px;",
        }).appendTo(row);
        row.picture.img = $(document.createElement('img')).attr({
            "alt": extension.name,
            "title": extension.name,
            "class": 'rounded-circle',
            "style": "width: 122px; height: 122px;",
            "src": extension.picture || '/plugins/extensions/placeholder.png',
        }).appendTo(row.picture);

        // Meta
        row.meta = $(document.createElement('div')).addClass('flex-grow-1 ps-3').appendTo(row);
        row.meta.header = $(document.createElement('h4')).appendTo(row.meta);
        row.meta.header.name = $(document.createElement('strong')).attr('data-key','name').text(extension.name).appendTo(row.meta.header);
        row.meta.header.by = $(document.createElement('span')).addClass('mx-2').text(builder.Locale.get('by')).appendTo(row.meta.header);
        row.meta.header.author = $(document.createElement('a')).attr({
            "class": "d-flex-inline align-items-center",
            "href": 'mailto:'+extension.email,
        }).appendTo(row.meta.header);
        row.meta.header.author.avatar = $(document.createElement('img')).attr({
            "alt": builder.Locale.get('Author') + ': ' + extension.author,
            "title": builder.Locale.get('Author') + ': ' + extension.author,
            "class": 'rounded-circle me-1',
            "style": "width: 32px; height: 32px;",
            "src": '/avatar?username='+extension.email,
        }).appendTo(row.meta.header.author);
        row.meta.header.author.name = $(document.createElement('span')).attr('data-key','author').text(extension.author).appendTo(row.meta.header.author);
        row.meta.paragraph = $(document.createElement('p')).attr('data-key','description').text(extension.description).appendTo(row.meta);

        // Links
        row.meta.links = $(document.createElement('div')).addClass('d-flex flex-row').appendTo(row.meta);
        if(typeof extension.repository !== 'undefined' && extension.repository !== null && extension.repository !== '') {
            row.meta.links.repo = $(document.createElement('a')).attr({
                "href": extension.repository,
                "target": "_blank",
                "class": "btn btn-sm btn-outline-secondary me-2",
            }).text(builder.Locale.get('Repository')).appendTo(row.meta.links);
            row.meta.links.repo.icon = $(document.createElement('i')).addClass('bi bi-git me-1').css('color','var(--bs-orange)').prependTo(row.meta.links.repo);
        }
        if(typeof extension.tracker !== 'undefined' && extension.tracker !== null && extension.tracker !== '') {
            row.meta.links.tracker = $(document.createElement('a')).attr({
                "href": extension.tracker,
                "target": "_blank",
                "class": "btn btn-sm btn-outline-secondary me-2",
            }).text(builder.Locale.get('Tracker')).appendTo(row.meta.links);
            row.meta.links.tracker.icon = $(document.createElement('i')).addClass('bi bi-bug-fill me-1').css('color','var(--bs-indigo)').prependTo(row.meta.links.tracker);
        }
        if(typeof extension.support !== 'undefined' && extension.support !== null && extension.support !== '') {
            row.meta.links.support = $(document.createElement('a')).attr({
                "href": extension.support,
                "target": "_blank",
                "class": "btn btn-sm btn-outline-secondary me-2",
            }).text(builder.Locale.get('Donation')).appendTo(row.meta.links);
            row.meta.links.support.icon = $(document.createElement('i')).addClass('bi bi-heart-fill me-1').css('color','var(--bs-pink)').prependTo(row.meta.links.support);
        }

        // Manual Install Alert
        if(extension.installed && !extension.initialized && !extension.published){
            row.meta.initialize = $(document.createElement('div')).attr({
                "class": "alert alert-warning mt-3 p-2 px-3",
            }).text(builder.Locale.get('This extension was manually installed.')).appendTo(row.meta);
            row.meta.initialize.icon = $(document.createElement('i')).addClass('bi bi-exclamation-triangle-fill me-1').css('color','var(--bs-warning)').prependTo(row.meta.initialize);
        }

        // Git Install Alert
        if(extension.initialized){
            row.meta.initialize = $(document.createElement('div')).attr({
                "class": "alert alert-danger mt-3 p-2 px-3",
            }).text(builder.Locale.get('This extension was installed using Git.')).appendTo(row.meta);
            row.meta.initialize.icon = $(document.createElement('i')).addClass('bi bi-exclamation-triangle-fill me-1').css('color','var(--bs-danger)').prependTo(row.meta.initialize);
        }

        // Version
        row.version = $(document.createElement('div')).addClass('flex-shrink-0 px-3').appendTo(row);
        row.version.header = $(document.createElement('h5')).addClass('m-0').appendTo(row.version);
        row.version.badge = $(document.createElement('span')).addClass('badge text-bg-blue').attr('data-key','version').text(extension.version).appendTo(row.version.header);

        // Controls
        row.controls = $(document.createElement('div')).addClass('flex-shrink-0 btn-group-vertical').appendTo(row);
        if(extension.installed){
            if(!extension.latest){
                row.controls.update = $(document.createElement('button')).attr({
                    "class": "btn btn-warning",
                }).text(builder.Locale.get('Update')).appendTo(row.controls);
                row.controls.update.icon = $(document.createElement('i')).addClass('bi bi-arrow-clockwise me-1').prependTo(row.controls.update);
            }
            if(extension.initialized){
                if(!extension.published) {
                    row.controls.publish = $(document.createElement('button')).attr({
                        "class": "btn btn-blue",
                    }).text(builder.Locale.get('Publish')).appendTo(row.controls);
                    extension.published.icon = $(document.createElement('i')).addClass('bi bi-check-lg me-1').prependTo(row.controls.publish);
                }
                row.controls.dev = $(document.createElement('button')).attr({
                    "class": "btn btn-indigo",
                }).text(builder.Locale.get('DEV')).appendTo(row.controls);
                row.controls.dev.icon = $(document.createElement('i')).addClass('bi bi-code-slash me-1').prependTo(row.controls.dev);
                row.controls.dev.click(function(){
                    ExtensionsModalDev(extension, row);
                });
            } else {
                row.controls.uninstall = $(document.createElement('button')).attr({
                    "class": "btn btn-danger",
                }).text(builder.Locale.get('Uninstall')).appendTo(row.controls);
                row.controls.uninstall.icon = $(document.createElement('i')).addClass('bi bi-trash me-1').prependTo(row.controls.uninstall);
            }
        } else {
            row.controls.install = $(document.createElement('button')).attr({
                "class": "btn btn-success",
            }).text(builder.Locale.get('Install')).appendTo(row.controls);
            row.controls.install.icon = $(document.createElement('i')).addClass('bi bi-download me-1').prependTo(row.controls.install);
        }

        // Bind to Search
        builder.Search.set(row);
    }
}
