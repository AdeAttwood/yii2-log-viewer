var vueOptions = {
    el: "#logs-table",
    data: {
        tbldata: [],
        filters: window.jsonVueData.filters,
        levels: {},
        date: {
            to   : new Date( $( 'input[name="date_to"]' ).val() ).getTime(),
            from : new Date( $( 'input[name="date_from"]' ).val() ).getTime()
        },
        sortedDataCount: 0,
        logCount: 0,
        reverse: false,
        sortKey: "time",
        loading: false,
        errorMessage: false
    },
    computed: {},
    methods: {},
    filters: {},
};

vueOptions.mounted = function() {
    this.loading = true;
    var url = window.jsonVueData.getLogsRoute;
    if( window.location.search.substring( 1 ) ) {
        url = url + '?' + window.location.search.substring( 1 );
    }

    window.jQuery.get( url,{}, function( response ) {
        window.table.logCount = response.logs.length;
        window.table.tbldata = response.logs;
        window.table.levels = response.levels;
        window.table.loading = false;
        window.jQuery( '.loading' ).remove();
    }).fail( function( response ) {
        window.table.errorMessage = response.responseJSON.message;
    });
}

vueOptions.computed.sortedData = function () {
    var sortedData = this.tbldata,
        filters = this.filters,
        dateTo   = new Date( this.date.to ),
        dateFrom = new Date( this.date.from ),
        sortKey = this.sortKey;
        
    sortedData = sortedData.filter(function(item) {
        var itemDate = new Date( item.time );

        if ( dateFrom < itemDate && dateTo > itemDate ) {
            return true;
        }
        return false;
    });

    for (var filterItem in filters) {
        if (filters[filterItem]) {
            sortedData = sortedData.filter(function(item) {
                if ( filterItem == 'time' ) {
                    var field = new Date ( item[filterItem] ).toString();
                } else {
                    var field = item[filterItem];
                }
                var match = filters[filterItem];

                return field.search(new RegExp(match, "i")) > -1;
            });
        }
    }
    
    if (this.reverse) {
        sortedData = sortedData.sort(function(a, b) {
            if ( sortKey == 'time' ) {
                var dateA = new Date( a[sortKey] ),
                    dateB =  new Date( b[sortKey] );
                return dateA - dateB;
            }
            
            return a[sortKey] < b[sortKey];
        });
    } else {
        sortedData = sortedData.sort(function(a, b) {
            if ( sortKey == 'time' ) {
                var dateA = new Date( a[sortKey] ),
                    dateB =  new Date( b[sortKey] );
                return dateB - dateA;
            }

            return a[sortKey] > b[sortKey];
        });
    }
    
    this.sortedDataCount = sortedData.length;
    return sortedData;
}

vueOptions.methods.sort = function(sortKey) {
    this.reverse = this.sortKey == sortKey ? !this.reverse : false;
    this.sortKey = sortKey;
}

vueOptions.methods.getQueryParam = function( name ) {
    var url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

vueOptions.methods.showModel = function( index ) {
    var item = this.sortedData[ index ]
        html = "<pre>";

    html += this.sortedData[ index ].vars;
    html += "</pre>";

    $( "#details-modal .modal-body" ).html( html );
    $( "#details-modal .modal-header-h2" ).html( new Date( item.time ).toString() );

    $( "#details-modal" ).modal( "show" );
}

vueOptions.filters.dateTime = function (item) {
  return new Date( item ).toString();
};

var table = new Vue( vueOptions );

$( 'input[name="date_from"]' ).on( 'change', function( a ) {
    table.date.from = new Date( $( this ).val() ).getTime();
} );

$( 'input[name="date_to"]' ).on( 'change', function( a ) {
    table.date.to = new Date( $( this ).val() ).getTime();
} );

$( 'select[name="log-files"]' ).change( function() {
    if ( !$( this ).val() ) {
        window.location.href = window.location.pathname;
    }
    var file = this.options[this.selectedIndex].text;
    window.location.href = window.location.pathname + '?file=' + file;
} );