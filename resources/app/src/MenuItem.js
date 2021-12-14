class MenuItem {
    constructor(label, id, route = null, contents = null, handler = null) {
        if (typeof label === 'object') {
            this.label = label.label;
            this.route = label.route || null;
            this.contents = label.contents;
            this.handler = label.handler;
            this.id = label.id;
            return    
        }

        this.label = label;
        this.route = route;
        this.contents = contents;
        this.handler = handler;
        this.id = id
    }

    get hasContents() {
        return (this.contents && this.contents.length > 0);
    }

    get hasRoute() {
        return this.route;
    }

    get hasHandler() {
        return Boolean(this.handler);
    }
}

export default MenuItem;