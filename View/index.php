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

                                    // Retrieve the first key of the response
                                    const first = Object.keys(response)[0];

                                    // Handle success response
                                    for(const [base, extension] of Object.entries(response)){

                                        // Create a row
                                        const row = $(document.createElement('div')).addClass('d-flex flex-row align-items-center').appendTo(tab);

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
                                            "src": extension.picture || '/assets/images/placeholder.png',
                                        }).appendTo(row.picture);

                                        // Meta
                                        row.meta = $(document.createElement('div')).addClass('flex-grow-1 ps-3').appendTo(row);
                                        row.meta.header = $(document.createElement('h4')).appendTo(row.meta);
                                        row.meta.header.name = $(document.createElement('strong')).text(extension.name).appendTo(row.meta.header);
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
                                        row.meta.header.author.name = $(document.createElement('span')).attr({
                                        }).text(extension.author).appendTo(row.meta.header.author);
                                        row.meta.paragraph = $(document.createElement('p')).text(extension.description).appendTo(row.meta);
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

                                        // Initialization Alert
                                        if(!extension.initialized && !extension.published){
                                            row.meta.initialize = $(document.createElement('div')).attr({
                                                "class": "alert alert-danger mt-3 p-2 px-3",
                                            }).text(builder.Locale.get('This extension has not been initialized')).appendTo(row.meta);
                                            row.meta.initialize.icon = $(document.createElement('i')).addClass('bi bi-exclamation-triangle-fill me-1').css('color','var(--bs-danger)').prependTo(row.meta.initialize);
                                        }

                                        // Version
                                        row.version = $(document.createElement('div')).addClass('flex-shrink-0 px-3').appendTo(row);
                                        row.version.header = $(document.createElement('h5')).addClass('m-0').appendTo(row.version);
                                        row.version.badge = $(document.createElement('span')).addClass('badge text-bg-blue').text(extension.version).appendTo(row.version.header);

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
                            });
                        },
                    );
                }
            },
        );
    });
</script>
