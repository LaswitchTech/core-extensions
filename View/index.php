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

                        // Loop through the types
                        for(const [type, extensions] of Object.entries(response)){

                            // Add a tab for each type
                            tabs.add(
                                type,
                                {label: builder.Locale.get(type.charAt(0).toUpperCase() + type.slice(1))},
                                function(tab, nav){

                                    // Generate the feed
                                    ExtensionsFeed(tab, extensions);
                                },
                            );
                        }

                        // Add a tab for importation
                        tabs.add(
                            "import",
                            {label: builder.Locale.get("Import")},
                            function(tab, nav){},
                        );
                    }
                });
            },
        );
    });
</script>
