<!--
  Core Framework - View File

  @license    MIT (https://mit-license.org/)
  @author     Full Name <user@domain.com>
-->
Extension Manager

- Manage Listings
- Manage a List (submit)


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

                // Loop through the types
                // for(const [key, type] of Object.entries(['modules', 'plugins', 'themes'])){
                for(const [key, type] of Object.entries(['plugins'])){

                    // Add a tab for each type
                    tabs.add(
                        key,
                        {label: builder.Locale.get(type.charAt(0).toUpperCase() + type.slice(1))},
                        function(tab, nav){
                            console.log(type, tab, nav)

                            // Styling
                            // tab.addClass('d-flex');

                            // AJAX Request
                            $.ajax({
                                url: '/endpoint.php/extensions/fetchAll?type=' + type,
                                type: 'GET',dataType: 'json',
                                error: function(xhr, status, error) {
                                    let color = 'info', icon = 'question-circle', title = builder.Locale.get(xhr.statusText), content = builder.Locale.get(xhr.responseText);
                                    switch(xhr.status){
                                        case 403: color = 'danger'; icon = 'person'; break;
                                        case 404: color = 'warning'; icon = 'question-diamond'; break;
                                        case 500: color = 'danger'; icon = 'bug'; break;
                                    }
                                    builder.Component("alert",tab,{icon:icon,color:color,title:title},function(alert,component){component.content.html('<pre class="m-0 p-2">'+content+'</pre>');});
                                },
                                success: function(response) {

                                    // Generate the feed
                                    ExtensionsFeed(tab, response);
                                }
                            });
                        },
                    );
                }
            },
        );
    });
</script>
