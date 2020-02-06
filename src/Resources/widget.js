(function ($) {

    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');

    // A customised widget to tweak some output from default Query Widget
    var LaravelSQLQuerySummaryWidget = PhpDebugBar.Widgets.LaravelSQLQuerySummaryWidget = PhpDebugBar.Widgets.LaravelSQLQueriesWidget.extend({
        tagName: 'div',

        render: function () {
            // call the parent to render the data, then we'll tweak it
            LaravelSQLQuerySummaryWidget.__super__.render.apply(this);
            var self = this;

            // add another function bound to the data attr being set, so it'll run after the parent one
            this.bindAttr('data', function (data) {
                lis = this.$list.$el.find("li.phpdebugbar-widgets-list-item");
                // loop through the top level, unique statements, tweaking each one as we go
                $(data.statements).each(function (i, item) {
                    tableBody = $(lis[i]).find("table");
                    // empty the existing meta data table, and add new headings
                    tableBody
                        .empty()
                        .append($('<tr />')
                            .append($('<td />').html('Count'))
                            .append($('<td />').html('Duration'))
                            .append($('<td />').html('Backtrace'))
                        )
                    // populate subgroups of sources where this query was called from
                    $(Object.entries(item.subCount)).each(function (i, subItem) {
                        // create row, and add count and duration cells
                        subGroup = $('<tr />')
                            .append($('<td />').html(subItem[1].count))
                            .append($('<td />').html(subItem[1].duration_str))

                        // create cell for source backtrace
                        var $span = $('<span />').addClass('phpdebugbar-text-muted');
                        var $bindings = new PhpDebugBar.Widgets.ListWidget({ itemRenderer: function(li, binding) {
                            var $index = $span.clone().text(binding[1].index + '.');
                            li.append($index, '&nbsp;', binding[1].name).removeClass(csscls('list-item')).addClass(csscls('table-list-item'));
                            li.append($span.clone().text(':' + binding[1].line));

                        }});

                        $bindings.set('data', Object.entries(subItem[1].source));
                        $bindings.$el.removeClass(csscls('list')).addClass(csscls('table-list'));

                        tableBody.append(subGroup.append($('<td />').append($bindings.$el)));
                    })
                });
            });
        },
    });
})(PhpDebugBar.$);