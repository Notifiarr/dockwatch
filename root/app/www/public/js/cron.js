/**
 * jQuery Plugin to generate/validate cron expressions
 *
 * Added Mon, 11 Mar 2024
 * https://github.com/Notifiarr/dockwatch
 *
 */

(function ($) {
    "use strict";

    let pluginName = "cron",
        defaults = {
            expression: "0 0 * * *",
            hash: null,
            name: null,
            onChange: undefined,
        };

    function Plugin(element, options) {
        this.element = element;
        this.opts = $.extend({}, defaults, options);

        this._defaults = defaults;
        this._name = pluginName;

        this.destroy(); // destroy other instances
        this.init();
    }

    function testExpression(expression, name, notify) {
        // sources:
        // https://github.com/shawnchin/jquery-cron/blob/59a6e7c38b56ef9b58441364145b8ce26a9a50a3/cron/jquery-cron.js#L159-L166
        // https://regexr.com/5bdes
        let cronRegEx = {
            combined: /^((((\d+,)+\d+|(\d+(\/|-|#)\d+)|\d+L?|\*(\/\d+)?|L(-\d+)?|\?|[A-Z]{3}(-[A-Z]{3})?) ?){5,7})$/,
            minute: /^(\*\s){4}\*$/, // "* * * * *"
            hour: /^\d{1,2}\s(\*\s){3}\*$/, // "? * * * *"
            day: /^(\d{1,2}\s){2}(\*\s){2}\*$/, // "? ? * * *"
            week: /^(\d{1,2}\s){2}(\*\s){2}\d{1,2}$/, // "? ? * * ?"
            month: /^(\d{1,2}\s){3}\*\s\*$/, // "? ? ? * *"
            year: /^(\d{1,2}\s){4}\*$/, // "? ? ? ? *"
        };

        let regExFailed = true;
        Object.entries(cronRegEx).forEach(([key, value]) => {
            if (value.test(expression)) {
                regExFailed = false;
                return;
            }
        });

        if (regExFailed) {
            toast("Frequency Cron Editor", "Expression is invalid!", "error");
            return;
        }

        const minute = parseInt(expression.match(/^[0-9]+(?=\s)/));
        // if (minute % 5 !== 0 || minute < 0 || minute > 55) {
        //     toast("Frequency Cron Editor", "Minute has to be >= 0, <= 55 & divisble by 5", "error");
        //     return;
        // }
        if (minute < 0 || minute > 59) {
            toast("Frequency Cron Editor", "Minute has to be >= 0 and <= 59", "error");
            return;
        }

        return true;
    }

    $.extend(Plugin.prototype, {
        destroy: function () {
            // unbind events
            $("#cronFreqEditor-applyExpression").off("click");
            $("#cronFreqEditor-hourCheckbox").off("click");
            $("#cronFreqEditor-dropdownCheckbox").off("click");
            $(".weekDaysButtons").off("click");
            $("#cronFreqEditor-hourInput").off("change");
            $("#cronFreqEditor-minuteInput").off("change");
            $("#cronFreqEditor-hourDropdown").off("change");
            $("#cronFreqEditor-minuteDropdown").off("change");
            $("#cronFreqEditor-result").off("change");

            // delete data
            $(this.element).unbind().removeData();

            // empty in case there is still stuff in there
            $(this.element).empty();
        },
        init: function () {
            const root = $(this.element);
            let content = [],
                selectedWeekDays = [],
                weekDays = ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"],
                hourOptions = [],
                minuteOptions = [],
                weekDaysButtons = [];

            // options and buttons
            for (let i = 1; i <= 23; i++) {
                hourOptions.push(`<option value="${i}">${i < 10 ? "0" + i : i}</option>`);
            }

            for (let i = 0; i <= 59; i++) {
                if (i % 5 == 0) {
                    minuteOptions.push(`<option value="${i}">${i < 10 ? "0" + i : i}</option>`);
                }
            }

            for (let i = 0; i <= 6; i++) {
                let randomId = (Math.random() + 1).toString(36).substring(7);
                weekDaysButtons.push(`<input type="checkbox" class="btn-check weekDaysButtons" id="${randomId}" autocomplete="off" data-day="${i < 6 ? weekDays[i + 1] : weekDays[0]}"><label class="btn btn-outline-primary" for="${randomId}">${i < 6 ? weekDays[i + 1] : weekDays[0]}</label>`);
            }

            content.push(`
                <div class="bg-secondary rounded pt-4 px-4 mb-4">
                    <div class="row mb-3">
                        <div class="col-sm-3">Period of task</div>
                        <div class="col-sm-9">
                            <div class="mb-2">
                                <input type="radio" class="form-check-input" name="cronFreqEditor-period" id="cronFreqEditor-hourCheckbox">
                                <label for="cronFreqEditor-hourCheckbox">Time of the day</label>
                                <select class="form-select d-inline-block" style="width: 80px;" id="cronFreqEditor-hourInput">${hourOptions.join("\n")}</select> :
                                <select class="form-select d-inline-block" style="width: 80px;" id="cronFreqEditor-minuteInput">${minuteOptions.join("\n")}</select>
                            </div>
                            <div>
                                <input type="radio" class="form-check-input" name="cronFreqEditor-period" id="cronFreqEditor-dropdownCheckbox">
                                <label for="cronFreqEditor-dropdownCheckbox">Every</label>
                                <select class="form-select d-inline-block" style="width: 80px;" id="cronFreqEditor-hourDropdown">${hourOptions.join("\n")}</select>
                                hour(s) at minute
                                <select class="form-select d-inline-block" style="width: 80px;" id="cronFreqEditor-minuteDropdown">${minuteOptions.join("\n")}</select>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">Day of the week</div>
                        <div class="col-sm-9">
                            <div class="btn-group" role="group">${weekDaysButtons.join("")}</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3">Cron output</div>
                        <div class="col-sm-9">
                            <input class="form-control d-inline-block result me-2 w-25" type="text" id="cronFreqEditor-result" value="0 23 * * 0" placeholder="0 23 * * 0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <div class="d-flex justify-content-center">
                                <button type="button" id="cronFreqEditor-applyExpression" class="btn btn-outline-info mt-2">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            root.empty();
            root.html(content.join(""));

            $("#cronFreqEditor-hourCheckbox").prop("checked", true);

            function toggleCheckbox(value) {
                if (value) {
                    $("#cronFreqEditor-hourInput").trigger("change");
                    $("#cronFreqEditor-minuteInput").trigger("change");
                } else {
                    $("#cronFreqEditor-hourDropdown").trigger("change");
                    $("#cronFreqEditor-minuteDropdown").trigger("change");
                }
            }

            function toggleDay(e) {
                const day = weekDays.indexOf(e.target.dataset.day);

                if (selectedWeekDays.includes(day)) {
                    selectedWeekDays = selectedWeekDays.filter((v) => v !== day);
                } else {
                    selectedWeekDays.push(Number(day));
                }

                expression[4] = "*";
                if (selectedWeekDays.length > 0) {
                    expression[4] = selectedWeekDays.join(",");
                }

                $("#cronFreqEditor-result").val(expression.join(" "));
            }

            // parse expression
            let expression = this.opts.expression;
            if (typeof expression !== "string" || expression == "") {
                expression = "0 23 * * 0";
            }
            expression = expression.split(" "); // split into array

            // apply the expression to the inputs here
            if (expression[1].startsWith("*/")) {
                toggleCheckbox(false);

                $(`#cronFreqEditor-hourDropdown option[value='${expression[1].split("*/")[1]}']`).attr("selected", "selected");
                $(`#cronFreqEditor-minuteDropdown option[value='${expression[0]}']`).attr("selected", "selected");
            } else {
                toggleCheckbox(true);

                $(`#cronFreqEditor-hourInput option[value='${expression[1]}']`).attr("selected", "selected");
                $(`#cronFreqEditor-minuteInput option[value='${expression[0]}']`).attr("selected", "selected");
            }

            let days = expression[4].split(',');
            if (days.length && days[0] != '*') {
                for (let d = 0; d < days.length; d++) {
                    $('[data-day="' + weekDays[days[d]] + '"]').prop('checked', true);
                    selectedWeekDays.push(Number(days[d]));
                }
            }

            $("#cronFreqEditor-result").attr("value", expression.join(" "));

            // events
            const onChangeFn = this.opts.onChange;
            const containerName = this.opts.name;
            if (typeof onChangeFn === "function") {
                $("#cronFreqEditor-applyExpression").on("click", function () {
                    if (testExpression($("#cronFreqEditor-result").val(), containerName, true) == true) {
                        $("#cronFreqEditor-applyExpression").off("click");

                        onChangeFn($("#cronFreqEditor-result").val());

                        $("#cronFreqEditor-applyExpression").attr("data-bs-dismiss", "modal");
                        $("#cronFreqEditor-applyExpression[data-bs-dismiss=modal]").get(0).click();
                    }
                });
            }
            $("#cronFreqEditor-hourCheckbox").on("click", function () {
                toggleCheckbox(true);
            });
            $("#cronFreqEditor-dropdownCheckbox").on("click", function () {
                toggleCheckbox(false);
            });
            $(".weekDaysButtons").on("click", function (e) {
                toggleDay(e);
            });
            $("#cronFreqEditor-hourInput").on("change", function (e) {
                let val = parseInt(e.target.value);

                expression[1] = `${val}`;
                $("#cronFreqEditor-result").val(expression.join(" "));

                $("#cronFreqEditor-applyExpression").prop("disabled", false);
            });
            $("#cronFreqEditor-minuteInput").on("change", function (e) {
                let val = parseInt(e.target.value);

                expression[0] = `${val}`;
                $("#cronFreqEditor-result").val(expression.join(" "));

                $("#cronFreqEditor-applyExpression").prop("disabled", false);
            });
            $("#cronFreqEditor-hourDropdown").on("change", function (e) {
                let val = parseInt(e.target.value);

                expression[1] = `*/${val}`;
                $("#cronFreqEditor-result").val(expression.join(" "));

                $("#cronFreqEditor-applyExpression").prop("disabled", false);
            });
            $("#cronFreqEditor-minuteDropdown").on("change", function (e) {
                let val = parseInt(e.target.value);

                expression[0] = val;
                $("#cronFreqEditor-result").val(expression.join(" "));

                $("#cronFreqEditor-applyExpression").prop("disabled", false);
            });
            $("#cronFreqEditor-result").on("change", function (e) {
                $("#cronFreqEditor-applyExpression").prop("disabled", false);
            });
        },
    });

    $.fn[pluginName] = function (options) {
        this.each(() => {
            if (!$.data(this, `plugin_${pluginName}`)) {
                $.data(this, `plugin_${pluginName}`, new Plugin(this, options));
            }
        });

        return this;
    };
})(jQuery);
