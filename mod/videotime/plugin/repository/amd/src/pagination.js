define(['jquery', 'core/templates'], function($, Templates) {

    var Pagination = function(perPage, totalItems, root) {
        this.perPage = perPage;
        this.currentPage = 0;
        this.totalItems = totalItems;
        this.root = root;
    };

    Pagination.prototype.getRoot = function() {
        return this.root;
    };

    Pagination.prototype.getContainer = function() {
        return this.root.find('.pagination-container');
    };

    Pagination.prototype.init = function() {
        this.getRoot().on('click', '[data-action=change-page]', function(e) {
            var target = $(e.target);
            var newPage = target.data('page');
            this.setCurrentPage(newPage);
            this.getContainer().trigger('pagination.changePage', [newPage]);
        }.bind(this));

        this.getRoot().on('change', '[data-action=jump-page]', function(e) {
            var target = $(e.target);
            var newPage = target.val();
            this.setCurrentPage(newPage);
            this.getContainer().trigger('pagination.changePage', [newPage]);
        }.bind(this));
    };

    Pagination.prototype.getTemplateContext = function() {
        var pages = [];
        var pageCount = this.getPageCount();
        var currentPage = this.getCurrentPage();

        for (var i = 0; i < pageCount; i++) {
            pages.push({
                'index': i,
                'label': i+1,
                'selected': i == currentPage
            });
        }

        return {
            'previous_disabled': this.getCurrentPage() == 0,
            'next_disabled': this.getCurrentPage() == this.getPageCount()-1 || !this.getPageCount(),
            'previous_page': this.getCurrentPage()-1,
            'next_page': this.getCurrentPage()+1,
            'pages': pages,
            'page_count': pageCount,
            'total': this.totalItems
        };
    };

    Pagination.prototype.getPageCount = function() {
        return Math.ceil(this.totalItems / this.perPage);
    };

    Pagination.prototype.getLimitFrom = function() {
        return this.currentPage * this.perPage;
    };

    Pagination.prototype.getCurrentPage = function() {
        return this.currentPage;

    };
    Pagination.prototype.setCurrentPage = function(currentPage) {
        this.currentPage = currentPage;

    };
    Pagination.prototype.setTotalItems = function(totalItems) {
        this.totalItems = totalItems;
    };

    Pagination.prototype.render = function() {
        Templates.render('videotimeplugin_repository/pagination', this.getTemplateContext())
            .then(function(html, js) {
                this.getContainer().html(html);
            }.bind(this));
    };

    return Pagination;
});
