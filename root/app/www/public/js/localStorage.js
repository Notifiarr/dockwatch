/*
----------------------------------
 ------  Created: 112225   ------
 ------  nzxl	             ------
----------------------------------
*/

function getLocalStorage(localStorageKeys) {
    //-- INIT GLOBAL VARIABLE
    window.localStorageSettings = window.localStorageSettings || {};

    $.each(localStorageKeys, function (_, key) {
        try {
            const item = localStorage.getItem(key);
            const currentLocalStorage = item ? JSON.parse(item) : {};
            window.localStorageSettings[key] = currentLocalStorage;
            //-- CREATE EMPTY OBJ IF NOT EXIST
            if (!item) {
                localStorage.setItem(key, JSON.stringify(currentLocalStorage));
            }
        } catch (e) {
            //-- RESET OBJ IF CORRUPTED/INVALID JSON
            window.localStorageSettings[key] = {};
            localStorage.setItem(key, JSON.stringify(window.localStorageSettings[key]));
        }
    });

    return window.localStorageSettings;
}

function setLocalStorage(storageKey, key, val) {
    window.localStorageSettings = window.localStorageSettings || {};
    window.localStorageSettings[storageKey] = window.localStorageSettings[storageKey] || {};
    window.localStorageSettings[storageKey][key] = val;
    //-- SAVE TO LOCALSTORAGE
    localStorage.setItem(storageKey, JSON.stringify(window.localStorageSettings[storageKey]));
}
