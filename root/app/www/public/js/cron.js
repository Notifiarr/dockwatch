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
        if (minute % 5 !== 0 || minute < 0 || minute > 55) {
            toast("Frequency Cron Editor", "Minute has to be >= 0, <= 55 & divisble by 5", "error");
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
            $("#cron .weekDaysButtons button").off("click");
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
                weekDaysButtons.push(`<button id='${randomId}' type="button">${i < 6 ? weekDays[i + 1] : weekDays[0]}</button>`);
            }

            // stylesheet
            content.push(`
                <style>
                    #cron .container {
                        display: flex;
                        margin-bottom: 25px;
                    }
                    #cron .p-15 {
                        padding-right: 15px;
                    }
                    #cron .w-144 {
                        width: 144px;
                    }
                    #cron .mb-10 {
                        margin-bottom: 10px;
                    }
                    #cron p {
                        display: inline;
                    }
                    #cron .hourDropdown,
                    #cron .hourInput {
                        width: 50px !important;
                        display: inline;
                        border-color: var(--primary);
                        background-color: #000;
                    }
                    #cron .minuteDropdown,
                    #cron .minuteInput {
                        width: 50px !important;
                        display: inline;
                        border-color: var(--primary);
                        background-color: #000;
                    }
                    #cron .weekDaysButtons button {
                        margin: 0;
                        border: none;
                        width: 50px;
                        height: 50px;
                        font-size: 12px;
                        background-color: #1e1e1e;
                        color: var(--primary);
                        transition: 0.2s;
                        z-index: 1;
                        position: relative;
                    }
                    #cron .weekDaysButtons button:first-child,
                    #cron .weekDaysButtons button:last-child {
                        border-radius: 5px;
                        margin: 0 -3px 0;
                        z-index: 0;
                    }
                    #cron .weekDaysButtons button:hover {
                        background-color: #151515;
                    }
                    #cron .weekDaysButtons .disabled {
                        color: white;
                        background-color: var(--primary) !important;
                    }
                    #cron .result {
                        width: 150px !important;
                        display: block;
                        border-color: var(--primary);
                        margin-bottom: 10px;
                    }

                    #cron .form-check-input {
                        border: 1px solid var(--primary);
                        margin-top: .75em;
                    }
                </style>
            `);

            // html
            content.push(`
                <div class="container">
                    <div class="w-144">
                        <label class="p-15" for="pickTime">Period of task</label>
                    </div>
                    <div>
                        <div class="mb-10">
                            <input type="radio" class="form-check-input" name="cronFreqEditor-period" id="cronFreqEditor-hourCheckbox">
                            <label for="cronFreqEditor-hourCheckbox">Time of the day</label>
                            <select class="form-control hourInput" id="cronFreqEditor-hourInput">${hourOptions.join("\n")}</select>
                            <p>:</p>
                            <select class="form-control minuteInput" id="cronFreqEditor-minuteInput">${minuteOptions.join("\n")}</select>
                        </div>
                        <div>
                            <input type="radio" class="form-check-input" name="cronFreqEditor-period" id="cronFreqEditor-dropdownCheckbox">
                            <label for="cronFreqEditor-dropdownCheckbox">Every</label>
                            <select class="form-control hourDropdown" id="cronFreqEditor-hourDropdown">${hourOptions.join("\n")}</select>
                            <label>hours at minute</label>
                            <select class="form-control minuteDropdown" id="cronFreqEditor-minuteDropdown">${minuteOptions.join("\n")}</select>
                        </div>
                    </div>
                </div>
                <div class="container">
                    <div class="w-144">
                        <label for="repeat">Day of the week</label>
                    </div>
                    <div class="weekDaysButtons">${weekDaysButtons.join("")}</div>
                </div>
                <div class="container">
                    <div class="w-144">
                        <label for="output">Cron output</label>
                    </div>
                    <div>
                        <input class="form-control result" type="text" id="cronFreqEditor-result" value="0 23 * * 0">
                        <button type="button" id="cronFreqEditor-applyExpression" class="btn btn-success">Apply</button>
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
                const el = `#${e.target.id}`;
                const day = weekDays.indexOf($(el).text());

                if (selectedWeekDays.includes(day)) {
                    selectedWeekDays = selectedWeekDays.filter((v) => v !== day);
                } else {
                    selectedWeekDays.push(day);
                }

                if (selectedWeekDays.length <= 0) {
                    expression[4] = "*";
                } else {
                    expression[4] = selectedWeekDays.join(",");
                }

                $("#cronFreqEditor-result").val(expression.join(" "));

                if ($(el).hasClass("disabled")) {
                    $(el).removeClass("disabled");
                } else {
                    $(el).addClass("disabled");
                }
            }

            // parse expression - todo: allow ranges and step values 0-50../2..etc
            let expression = this.opts.expression;
            if (typeof this.opts.expression == "undefined") {
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

            $.each($("#cron .weekDaysButtons").children(), function () {
                const el = `#${this.id}`;
                let days = expression[4].split(",");

                for (let i = 0; i < days.length; i++) {
                    days[i] = Number(days[i]);
                }

                if (days.length > 0 && days[0] !== "*") {
                    for (let i = 0; i < days.length; i++) {
                        if (days[i] == weekDays.indexOf($(el).text())) {
                            $(el).addClass("disabled");

                            if (!selectedWeekDays.includes(days[i])) {
                                selectedWeekDays.push(days[i]);
                            }
                        }
                    }
                }
            });
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
            $("#cron .weekDaysButtons button").on("click", function (e) {
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
