"use strict";

// Class definition
var KTChartsWidget33 = function () {
    // Private methods
    var initChart = function(tabSelector, chartSelector, data, labels, initByDefault) {
        var element = document.querySelector(chartSelector);

        if (!element) {
            return;
        }
        
        var color = element.getAttribute('data-kt-chart-color');
        var height = parseInt(KTUtil.css(element, 'height'));
        
        var labelColor = KTUtil.getCssVariableValue('--bs-gray-500');
        var borderColor = KTUtil.getCssVariableValue('--bs-border-dashed-color');
        var baseColor = KTUtil.getCssVariableValue('--bs-' + color);    

        var options = {
            series: [{
                name: 'Etherium ',
                data: data
            }],            
            chart: {
                fontFamily: 'inherit',
                type: 'area',
                height: height,
                toolbar: {
                    show: false
                }
            },            
            legend: {
                show: false
            },
            dataLabels: {
                enabled: false
            },
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.2,
                    stops: [15, 120, 100]
                }
            },
            stroke: {
                curve: 'smooth',
                show: true,
                width: 3,
                colors: [baseColor]
            },
            xaxis: {
                categories: labels,
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false
                },
                offsetX: 20,
                tickAmount: 4,
                labels: {
                    rotate: 0,
                    rotateAlways: false,
                    show: false,
                    style: {
                        colors: labelColor,
                        fontSize: '12px'                       
                    }
                },
                crosshairs: {
                    position: 'front',
                    stroke: {
                        color: baseColor,
                        width: 1,
                        dashArray: 3
                    }
                },
                tooltip: {
                    enabled: true,
                    formatter: undefined,
                    offsetY: 0,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                tickAmount: 4,
                max: 4000,
                min: 1000,
                labels: {
                    show: false                    
                }
            },
            states: {
                normal: {
                    filter: {
                        type: 'none',
                        value: 0
                    }
                },
                hover: {
                    filter: {
                        type: 'none',
                        value: 0
                    }
                },
                active: {
                    allowMultipleDataPointsSelection: false,
                    filter: {
                        type: 'none',
                        value: 0
                    }
                }
            },
            tooltip: {
                style: {
                    fontSize: '12px'
                },
                y: {
                    formatter: function (val) {
                        return val + '$';
                    }
                }
            },
            colors: [baseColor],
            grid: {
                borderColor: borderColor,
                strokeDashArray: 3,
                yaxis: {
                    lines: {
                        show: true
                    }
                }
            },
            markers: {
                strokeColor: baseColor,
                strokeWidth: 3
            }
        };

        var chart = new ApexCharts(element, options);
        var init = false;
        var tab = document.querySelector(tabSelector);
        
        if (initByDefault === true) {
            chart.render();
            init = true;
        }        

        tab.addEventListener('shown.bs.tab', function (event) {
            if (init == false) {
                chart.render();
                init = true;
            }
        })
    }

    // Public methods
    return {
        init: function () {   
            initChart('#kt_charts_widget_33_tab_1', '#kt_charts_widget_33_chart_1', 
                [2100, 3200, 3200, 2400, 2400, 1800, 1800, 2400, 2400, 3200, 3200, 3000, 3000, 3250, 3250], 
                ['10AM', '10.30AM', '11AM', '11.15AM', '11.30AM', '12PM', '1PM', '2PM', '3PM', '4PM', '5PM', '6PM', '7PM', '8PM', '9PM'], 
                true
            );

            initChart('#kt_charts_widget_33_tab_2', '#kt_charts_widget_33_chart_2', 
                [2300, 2300, 2000, 3200, 3200, 2800, 2400, 2400, 3100, 2900, 3100, 3100, 2600, 2600, 3200], 
                ['Apr 01', 'Apr 02', 'Apr 03', 'Apr 04', 'Apr 05', 'Apr 06', 'Apr 07', 'Apr 08', 'Apr 09', 'Apr 10', 'Apr 11', 'Apr 12', 'Apr 13', 'Apr 14', 'Apr 15'], 
                false
            );

            initChart('#kt_charts_widget_33_tab_3', '#kt_charts_widget_33_chart_3', 
                [2600, 3200, 2300, 2300, 2000, 3200, 3100, 2900, 3200, 3200, 2600, 3100, 2800, 2400, 2400], 
                ['Apr 02', 'Apr 03', 'Apr 04', 'Apr 05', 'Apr 06', 'Apr 09', 'Apr 10', 'Apr 12', 'Apr 14', 'Apr 17', 'Apr 18', 'Apr 18', 'Apr 20', 'Apr 22', 'Apr 24'], 
                false
            );

            initChart('#kt_charts_widget_33_tab_4', '#kt_charts_widget_33_chart_4', 
                [1800, 1800, 2400, 2400, 3200, 3200, 3000, 2100, 3200, 3300, 2400, 2400, 3000, 3200, 3100], 
                ['Jun 2021', 'Jul 2021', 'Aug 2021', 'Sep 2021', 'Oct 2021', 'Nov 2021', 'Dec 2021', 'Jan 2022', 'Feb 2022', 'Mar 2022', 'Apr 2022', 'May 2022', 'Jun 2022', 'Jul 2022', 'Aug 2022'], 
                false
            );

            initChart('#kt_charts_widget_33_tab_5', '#kt_charts_widget_33_chart_5', 
                [3000, 2100, 3300, 3100, 1800, 1800, 2400, 2400, 3100, 3100, 2400, 2400, 3000, 2400, 2800], 
                ['Sep 2021', 'Oct 2021', 'Nov 2021', 'Dec 2021', 'Jan 2022', 'Feb 2022', 'Mar 2022', 'Apr 2022', 'May 2022', 'Jun 2022', 'Jul 2022', 'Aug 2022', 'Sep 2022', 'Oct 2022', 'Nov 2022'], 
                false
            )
        }   
    }
}();

// Webpack support
if (typeof module !== 'undefined') {
    module.exports = KTChartsWidget33;
}

// On document ready
KTUtil.onDOMContentLoaded(function() {
    KTChartsWidget33.init();
});


 