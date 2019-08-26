// Plugin defaults – added as a property on our plugin function.
$.fn.oasplugin.defaults = {
    from:  (new Date().getDate() - 1) ,          //yesterday
    until: (new Date(date.getFullYear(), 1, 1)), //first day of year
    
    showparameters: true                         //toggle "from/until" panel
};