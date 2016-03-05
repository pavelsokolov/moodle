M.mod_label = {};

M.mod_label.init = function (Y, cmid, initialstate) {
    new this.label(Y.one('#module-' + cmid));
    Y.one('#module-' + cmid).addClass(initialstate);
};

M.mod_label.label = function (node) {
    this.labelnode = node;
	if (node.one('.l_header')) {
    	node.one('.l_header').on('click', this.toggle, this);
    }
};

M.mod_label.label.prototype = {
    labelnode: null,
    
    toggle: function () {
        if (this.labelnode.hasClass('expanded')) {
            this.labelnode.removeClass('expanded');
        } else {
            this.labelnode.addClass('expanded');
        }
    }
};