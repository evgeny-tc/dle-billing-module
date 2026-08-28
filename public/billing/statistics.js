/**
 * DLE Billing — графики статистики (Highcharts)
 */
var BillingStatistics = (function () {
    'use strict';

    function baseOptions(yTitle) {
        return {
            credits: { enabled: false },
            yAxis: {
                min: 0,
                title: { text: yTitle || '' }
            }
        };
    }

    function render(config, containerId, yTitle) {
        var el = document.getElementById(containerId);

        if (!el || typeof Highcharts === 'undefined') {
            return;
        }

        var options = baseOptions(yTitle);
        options.title = { text: config.title || '' };
        options.subtitle = { text: config.subtitle || '' };

        if (config.categories) {
            options.xAxis = {
                categories: config.categories,
                tickmarkPlacement: 'on',
                title: { enabled: false }
            };
        }

        if (config.type === 'combo') {
            options.chart = { type: 'column' };
            options.yAxis = [
                {
                    min: 0,
                    title: { text: yTitle || '' }
                },
                {
                    min: 0,
                    max: config.yAxisMax || 100,
                    title: { text: '%' },
                    opposite: true
                }
            ];
            options.tooltip = { shared: true };
            options.plotOptions = {
                column: { grouping: true, shadow: false },
                line: {
                    lineWidth: 2,
                    marker: { enabled: true, radius: 3 }
                }
            };
            options.series = (config.series || []).map(function (serie) {
                return {
                    name: serie.name,
                    type: serie.type || 'column',
                    data: serie.data,
                    yAxis: serie.yAxis || 0
                };
            });
        } else if (config.type === 'pie') {
            options.chart = { type: 'pie' };
            options.tooltip = {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            };
            options.plotOptions = {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.1f}%'
                    }
                }
            };
            options.series = config.series || [];
        } else if (config.type === 'bar') {
            options.chart = { type: 'bar' };
            options.yAxis = {
                min: 0,
                max: config.yAxisMax || null,
                title: { text: yTitle || '' }
            };
            options.plotOptions = {
                bar: { dataLabels: { enabled: true } }
            };
            options.legend = {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'top',
                floating: true,
                borderWidth: 1,
                backgroundColor: '#FFFFFF',
                shadow: true
            };
            options.series = config.series || [];
        } else {
            options.chart = { type: config.type || 'area' };
            options.tooltip = {
                split: true,
                valueSuffix: yTitle ? ' (' + yTitle.split('(').pop().replace(')', '') : ''
            };

            if (config.stacking) {
                options.plotOptions = {
                    area: {
                        stacking: config.stacking,
                        lineWidth: 1,
                        marker: { lineWidth: 1 }
                    },
                    column: { stacking: config.stacking }
                };
            }

            options.series = config.series || [];
        }

        Highcharts.chart(containerId, options);
    }

    return { render: render };
})();

$('[data-rel=calendardate]').datetimepicker({
    format:'Y-m-d',
    closeOnDateSelect:true,
    yearStart: new Date().getFullYear() - 100,
    yearEnd: new Date().getFullYear() + 30,
    dayOfWeekStart: 1,
    timepicker:false,
    scrollInput:false,
    scrollMonth:false
});