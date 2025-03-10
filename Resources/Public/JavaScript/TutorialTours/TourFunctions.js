
define(['jquery'], function ($) {
    // Instance the tour
    //   console.log(TF.selectFormEngineInput("identifier",""));
    //   console.log(TF.selectFormEngineInput("cookie_services"));
    //   console.log(TF.selectFormEngineInput("cookie_services",".t3js-formengine-field-group .form-multigroup-item:nth-child(2) .form-wizards-element"));


    function selectFormEngineInput(name,selector = "",elementOnly = false){
        // Select all elements with attribute containing 'data[*]'
        var elements = document.querySelectorAll('input[data-formengine-input-name*="data"],textarea[name*="data"],select[name*="data"],input.inlineRecord');
        // Loop through the elements and find the one with the unknown identifier
        var targetElement;
        for (var i = 0; i < elements.length; i++) {
            var element = elements[i];
            var attributeName = element.getAttribute('data-formengine-input-name');

            if(typeof attributeName === "object"){
                attributeName = element.getAttribute('name');
            }
            // Check if the identifier is unknown (denoted by *)
            if (attributeName.includes('['+name+']')) {
                targetElement = element;
                break;
            }
        }

        // Perform operations on the targetElement
        if (targetElement) {
            if(selector !== ""){
                if(elementOnly === true){
                    return targetElement.closest(selector);
                }
                return targetElement.closest(selector);
            }
            if(elementOnly === true){
                return targetElement;
            }
            return targetElement.closest(selector);
        }
    }

    function languageSelectorFrontend(){
        if(document.querySelector("#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend .recordlist-heading-row:first-child .cfLanguageHook:first-child") !== null){
            return  "#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend .cfLanguageHook";
        }
        return "#t3-table-tx_cfcookiemanager_domain_model_cookiefrontend .recordlist-heading-row:first-child .cfLanguageHook";
    }

    return {
        selectFormEngineInput: selectFormEngineInput,
        languageSelectorFrontend: languageSelectorFrontend,
    };

});


