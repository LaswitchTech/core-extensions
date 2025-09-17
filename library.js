builder.add('widgets','extensions', class extends builder.ComponentClass {

    _init(){
        this._properties = {
            class: {
                component: null,
            },
            interval: 10000,
            autoStart: false,
            callback: {},
        };
        this._counter = 0;
        this._interval = null;
        this._extensions = {};
    }

    _create(){

        // Set Self
        const self = this;

        // Create Component
        this._component = $(document.createElement('div')).attr({
            'id': 'extensions' + this._id,
            'class': 'extensions-widget',
        });
        this._component.id = this._component.attr('id');

        // Check if a component class is set
        if(this._properties.class.component){
            this._component.addClass(this._properties.class.component);
        }

        // Create a controls container
        this._component.controls = $(document.createElement('div')).addClass('extensions-controls').prependTo(this._component);
        this._component.controls.group = $(document.createElement('div')).addClass('btn-group').appendTo(this._component.controls);
        this._component.controls.group.add = $(document.createElement('button')).attr({
            'class': 'btn btn-success',
            'data-action': 'add',
            'type': 'button',
        }).html('<i class="bi bi-plus-lg"></i>').appendTo(this._component.controls.group);
        // this._component.controls.group.grid = $(document.createElement('button')).attr({
        //     'class': 'btn btn-outline-secondary',
        //     'data-action': 'grid',
        //     'type': 'button',
        // }).html('<i class="bi bi-grid-3x3-gap"></i>').appendTo(this._component.controls.group);
        // this._component.controls.group.list = $(document.createElement('button')).attr({
        //     'class': 'btn btn-outline-secondary',
        //     'data-action': 'list',
        //     'type': 'button',
        // }).html('<i class="bi bi-list"></i>').appendTo(this._component.controls.group);

        // Create a search container
        this._component.search = $(document.createElement('input')).attr({
            'class': 'form-control',
            'type': 'search',
            'placeholder': this._builder.Locale.get('Search...'),
        }).prependTo(this._component.controls);
        this._component.search.on('input', function(){
            const search = this.value.toLowerCase();
            self._component.container.children('.col').each(function(){
                const content = $(this).text().toLowerCase();
                if(content.includes(search)){
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        // Create a container for the extensions
        this._component.container = $(document.createElement('div')).addClass('extensions-container').appendTo(this._component);
        this._component.container.on('click', '.controls, .controls *', function (e) {
            e.stopPropagation();
        });

        // AJAX Request
        API.endpoint('/extensions/fetchAll').execute(function(response){
            // Loop through the types
            for(const [type, extensions] of Object.entries(response)){

                // Skip keys that are not types
                if(['modules','plugins','themes'].includes(type)){

                    // Loop through the extensions
                    for(const [name, extension] of Object.entries(extensions)){
                        self.add(type, extension);
                    }
                }
            }
        });
    }

    add(type, record, param1 = null, param2 = null){

        // Set Self
        const self = this;

        let options = {};
        let callback = null;

        // Set selector, options, and callback
        [param1, param2].forEach(param => {
            if(param !== null){
                if (typeof param === 'object') {
                    options = param;
                } else if (typeof param === 'function') {
                    callback = param;
                }
            }
        });

        let properties = {
            class: {},
            callback: {},
        };

        // Configure Options
        for(const [key, value] of Object.entries(options)){
            if(typeof properties[key] !== 'undefined'){
                switch(key){
                    case"callback":
                        if(typeof properties[key] !== 'undefined'){
                            for(const [k, v] of Object.entries(value)){
                                if(typeof properties[key][k] !== 'undefined'){
                                    properties[key][k] = v;
                                }
                            }
                        }
                        break;
                    case"class":
                        for(const [section, classes] of Object.entries(value)){
                            if(properties[key][section] != null){
                                properties[key][section] += ' ' + classes;
                            } else {
                                properties[key][section] = classes;
                            }
                        }
                        break;
                    default:
                        properties[key] = value;
                        break;
                }
            }
        }

        // Check if the extension already exists
        if(this._extensions[record.id ?? (this._counter + 1)]){
            // this.edit(record.id, record);
            return this;
        }

        // Increment Post Count
        this._counter++;

        // Set ID
        const count = record.id ?? this._counter;
        const id = this._component.id + 'extension' + count;

        // Create Column
        let extension = $(document.createElement('div')).attr({
            'id': id,
            'class': 'col',
            'data-type': type,
        }).appendTo(this._component.container);
        extension.id = extension.attr('id');
        extension.data = record;

        // Create Card
        extension.card = $(document.createElement('div')).addClass('card h-100 card-hover').appendTo(extension);
        extension.card.body = $(document.createElement('div')).addClass('card-body').appendTo(extension.card);

        // Add extension information
        extension.card.body.info = $(document.createElement('div')).addClass('d-flex align-items-center gap-3').appendTo(extension.card.body);
        extension.avatar = $(document.createElement('img')).attr({
            'class':'avatar rounded-circle border border-3',
            'src': record.picture || '/plugins/extensions/placeholder.png',
            'alt': (record.name ?? 'Unknown').substring(0,2).toUpperCase(),
        }).appendTo(extension.card.body.info);
        extension.card.body.info.container = $(document.createElement('div')).addClass('flex-grow-1').appendTo(extension.card.body.info);

        // Add Meta Information
        extension.meta = $(document.createElement('h4')).addClass('m-0 fw-lighter').appendTo(extension.card.body.info.container);
        extension.meta.name = $(document.createElement('strong')).attr('data-key','name').text(record.name).appendTo(extension.meta);
        extension.meta.by = $(document.createElement('span')).addClass('mx-2').text(builder.Locale.get('by')).appendTo(extension.meta);
        extension.meta.author = $(document.createElement('a')).attr({
            "class": "d-flex-inline align-items-center text-decoration-none",
            "href": 'mailto:'+record.email,
        }).appendTo(extension.meta);
        extension.meta.author.avatar = $(document.createElement('img')).attr({
            "alt": builder.Locale.get('Author') + ': ' + record.author,
            "title": builder.Locale.get('Author') + ': ' + record.author,
            "class": 'rounded-circle me-1',
            "style": "width: 32px; height: 32px;",
            "src": '/avatar?username='+record.email,
        }).appendTo(extension.meta.author);
        extension.meta.author.name = $(document.createElement('span')).attr('data-key','author').text(record.author).appendTo(extension.meta.author);
        extension.meta.description = $(document.createElement('p')).addClass('mb-1').attr('data-key','description').text(record.description).appendTo(extension.card.body.info.container);

        // Add Tags
        extension.meta.tags = $(document.createElement('div')).addClass('mb-2').appendTo(extension.card.body.info.container);
        if(typeof record.tags !== 'undefined' && record.tags !== null && record.tags.split(',').length > 0){
            for(const tag of record.tags.split(',')){
                $(document.createElement('span')).addClass('badge text-bg-secondary me-1').attr('data-key','tags').html('<i class="bi bi-tag-fill me-1"></i>'+tag).appendTo(extension.meta.tags);
            }
        } else {
            $(document.createElement('span')).addClass('badge text-bg-secondary me-1').attr('data-key','tags').html('<i class="bi bi-tag-fill me-1"></i>'+self._builder.Locale.get('No Tags')).appendTo(extension.meta.tags);
        }

        // Links
        extension.meta.links = $(document.createElement('div')).addClass('d-flex flex-row mt-1').appendTo(extension.card.body.info.container);
        if(typeof record.repository !== 'undefined' && record.repository !== null && record.repository !== '') {
            extension.meta.links.repo = $(document.createElement('a')).attr({
                "href": record.repository,
                "target": "_blank",
                "class": "btn btn-sm btn-outline-secondary me-2",
            }).text(self._builder.Locale.get('Source')).appendTo(extension.meta.links);
            extension.meta.links.repo.icon = $(document.createElement('i')).addClass('bi bi-git me-1').css('color','var(--bs-orange)').prependTo(extension.meta.links.repo);
        }
        if(typeof record.tracker !== 'undefined' && record.tracker !== null && record.tracker !== '') {
            extension.meta.links.tracker = $(document.createElement('a')).attr({
                "href": record.tracker,
                "target": "_blank",
                "class": "btn btn-sm btn-outline-secondary me-2",
            }).text(self._builder.Locale.get('Bug Tracker')).appendTo(extension.meta.links);
            extension.meta.links.tracker.icon = $(document.createElement('i')).addClass('bi bi-bug-fill me-1').css('color','var(--bs-indigo)').prependTo(extension.meta.links.tracker);
        }
        if(typeof record.support !== 'undefined' && record.support !== null && record.support !== '') {
            extension.meta.links.support = $(document.createElement('a')).attr({
                "href": record.support,
                "target": "_blank",
                "class": "btn btn-sm btn-outline-secondary me-2",
            }).text(self._builder.Locale.get('Donation')).appendTo(extension.meta.links);
            extension.meta.links.support.icon = $(document.createElement('i')).addClass('bi bi-heart-fill me-1').css('color','var(--bs-pink)').prependTo(extension.meta.links.support);
        }

        // Manual Install Alert
        extension.meta.published = $(document.createElement('div')).attr({
            "class": "alert alert-warning mt-3 p-2 px-3",
        }).text(self._builder.Locale.get('This extension was manually installed.')).appendTo(extension.card.body.info.container);
        extension.meta.published.icon = $(document.createElement('i')).addClass('bi bi-exclamation-triangle-fill me-1').css('color','var(--bs-warning)').prependTo(extension.meta.published);
        if(record.published){
            extension.meta.published.addClass('d-none');
        }

        // Git Install Alert
        extension.meta.git = $(document.createElement('div')).attr({
            "class": "alert alert-danger mt-3 p-2 px-3",
        }).text(self._builder.Locale.get('This extension was installed using Git.')).appendTo(extension.card.body.info.container);
        extension.meta.git.icon = $(document.createElement('i')).addClass('bi bi-exclamation-triangle-fill me-1').css('color','var(--bs-danger)').prependTo(extension.meta.git);
        if(!record.git){
            extension.meta.git.addClass('d-none');
        }

        // Version
        extension.version = $(document.createElement('div')).addClass('flex-shrink-1').appendTo(extension.card.body.info);
        extension.version.header = $(document.createElement('h5')).addClass('m-0').appendTo(extension.version);
        extension.version.badge = $(document.createElement('span')).addClass('badge text-bg-blue').attr('data-key','version').text(record.current).appendTo(extension.version.header);

        // Add Body Controls
        extension.controls = $(document.createElement('div')).addClass('controls btn-group').appendTo(extension.card.body.info);
        extension.controls.install = $(document.createElement('button')).attr({
            'class':'btn btn-success',
            'type': 'button',
            'data-action': 'install',
        }).html('<i class="bi bi-download"></i>').appendTo(extension.controls).click(function(){
            ExtensionsModalInstall(record);
        });
        extension.controls.uninstall = $(document.createElement('button')).attr({
            'class':'btn btn-danger',
            'type': 'button',
            'data-action': 'uninstall',
        }).html('<i class="bi bi-trash"></i>').appendTo(extension.controls).click(function(){
            ExtensionsModalUninstall(record);
        });
        extension.controls.update = $(document.createElement('button')).attr({
            'class':'btn btn-warning',
            'type': 'button',
            'data-action': 'update',
        }).html('<i class="bi bi-arrow-clockwise"></i>').appendTo(extension.controls).click(function(){
            ExtensionsModalUpdate(record);
        });
        extension.controls.publish = $(document.createElement('button')).attr({
            'class':'btn btn-blue',
            'type': 'button',
            'data-action': 'publish',
        }).html('<i class="bi bi-check-lg"></i>').appendTo(extension.controls).click(function(){
            ExtensionsModalPublish(record, options);
        });
        extension.controls.unpublish = $(document.createElement('button')).attr({
            'class':'btn btn-danger',
            'type': 'button',
            'data-action': 'unpublish',
        }).html('<i class="bi bi-x-lg"></i>').appendTo(extension.controls).click(function(){
            ExtensionsModalUnpublish(record);
        });
        extension.controls.dev = $(document.createElement('button')).attr({
            'class':'btn btn-indigo',
            'type': 'button',
            'data-action': 'dev',
        }).html('<i class="bi bi-code-slash"></i>').appendTo(extension.controls).click(function(){
            ExtensionsModalDev(record, extension);
        });
        if(record.installed){
            extension.controls.install.remove();
            if(record.latest){
                extension.controls.update.remove();
            }
            if(record.git){
                extension.controls.uninstall.remove();
                if(record.published) {
                    extension.controls.publish.remove();
                } else {
                    extension.controls.unpublish.remove();
                }
            } else {
                extension.controls.publish.remove();
                extension.controls.unpublish.remove();
                extension.controls.dev.remove();
            }
        } else {
            extension.controls.uninstall.remove();
            extension.controls.publish.remove();
            extension.controls.unpublish.remove();
            extension.controls.dev.remove();
            extension.controls.update.remove();
        }

        // Save the vCard in the contacts object
        this._extensions[count] = extension;

        // return the instance
        return this;
    }
});
