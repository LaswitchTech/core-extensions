<!--
  Core Framework - View File

  @license    MIT (https://mit-license.org/)
  @author     Full Name <user@domain.com>
-->
<div class="col-12" id="layout"></div>
<script>
    $(document).ready(function(){

        // Create a tabs component
        const Tabs = builder.Component(
            "tabs",
            "#layout",
            {
                class: {
                    navbar: 'nav-pills',
                },
                icon: 'puzzle',
                title: builder.Locale.get('Extensions'),
            },
            function(tabs,component){

                // AJAX Request
                $.ajax({
                    url: '/endpoint.php/extensions/fetchAll',
                    type: 'GET',dataType: 'json',
                    error: function(xhr, status, error) {
                        let color = 'info', icon = 'question-circle', title = builder.Locale.get(xhr.statusText), content = builder.Locale.get(xhr.responseText);
                        switch(xhr.status){
                            case 403: color = 'danger'; icon = 'person'; break;
                            case 404: color = 'warning'; icon = 'question-diamond'; break;
                            case 500: color = 'danger'; icon = 'bug'; break;
                        }
                        builder.Component("alert","#layout",{icon:icon,color:color,title:title},function(alert,component){component.content.html('<pre class="m-0 p-2">'+content+'</pre>');});
                    },
                    success: function(response) {

                        // Build a list of options
                        var options = [
                            {id: 'local', text: builder.Locale.get('Local Application Feed')},
                        ];

                        // Check if modules are available
                        if(typeof response.modules !== 'undefined' && Object.entries(response.modules).length > 0){

                            // Loop through the modules
                            for(const [base, extension] of Object.entries(response.modules)){

                                // Check if the extension has git enabled
                                if(extension.git){
                                    options.push({id: base, text: builder.Locale.get('Module') + ': ' + extension.name});
                                }
                            }
                        }

                        // Loop through the types
                        for(const [type, extensions] of Object.entries(response)){

                            // Add a tab for each type
                            tabs.add(
                                type,
                                {label: builder.Locale.get(type.charAt(0).toUpperCase() + type.slice(1))},
                                function(tab, nav){

                                    // Generate the feed
                                    ExtensionsFeed(tab, extensions, options);
                                },
                            );
                        }

                        // Add a tab for importation
                        tabs.add(
                            "import",
                            {label: builder.Locale.get("Import")},
                            function(tab, nav){

                                // Form
                                tab.form = builder.Component(
                                    'form',
                                    tab,
                                    {
                                        class:{
                                            form: 'row g-3',
                                            field: 'col-12',
                                        },
                                        callback:{
                                            submit: function(form){

                                                // // AJAX Request
                                                // $.ajax({
                                                //     url: '/endpoint.php/extensions/meta?type='+extension.type+'&base='+extension.base,
                                                //     headers: {'X-CSRF-Authorization': CSRF_KEY},
                                                //     type: 'POST',dataType: 'json',
                                                //     data: {meta: form.val()},
                                                //     success: function(response){

                                                //         // Update CSRF
                                                //         CSRF_KEY = response.CSRF.key;
                                                //         CSRF_TOKEN = response.CSRF.token;

                                                //         // Loop through the tables
                                                //         for(const [key, value] of Object.entries(form.val())){
                                                //             switch(key){
                                                //                 default:
                                                //                     row.find('[data-key="'+key+'"]').text(value);
                                                //                     break;
                                                //             }
                                                //         }

                                                //         // Close the modal
                                                //         modal.hide();
                                                //     }
                                                // });
                                            },
                                        },
                                    },
                                    function(form,component){

                                        // URL
                                        form.add(
                                            {
                                                name: 'url',
                                                label: builder.Locale.get('URL'),
                                                icon: 'git',
                                                type: 'text',
                                            },
                                        );

                                        // Token
                                        form.add(
                                            {
                                                name: 'token',
                                                label: builder.Locale.get('Token'),
                                                icon: 'key',
                                                type: 'text',
                                            },
                                        );

                                        // Submit
                                        form.add(
                                            {
                                                name: 'import',
                                                label: builder.Locale.get('Import'),
                                                icon: 'download',
                                                type: 'submit',
                                            },
                                        );
                                    },
                                );
                            },
                        );
                    }
                });
            },
        );
    });
</script>
