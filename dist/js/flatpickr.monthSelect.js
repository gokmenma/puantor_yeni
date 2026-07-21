(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? module.exports = factory() :
  typeof define === 'function' && define.amd ? define(factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, global.monthSelectPlugin = factory());
})(this, (function () { 'use strict';

  var defaultConfig = {
    shorthand: false,
    dateFormat: "F Y",
    altFormat: "F Y",
    theme: "light"
  };

  function monthSelectPlugin(pluginConfig) {
    var config = Object.assign({}, defaultConfig, pluginConfig);
    return function (fp) {
      var self = {
        months: []
      };

      function clearUnselected() {
        for (var i = 0; i < 12; i++) {
          if (self.months[i]) {
            self.months[i].classList.remove("selected");
          }
        }
      }

      function selectMonth(cell) {
        var month = cell.month;
        var year = fp.currentYear;
        fp.setDate(new Date(year, month, 1), true);
        clearUnselected();
        cell.classList.add("selected");
      }

      function setupMonths() {
        if (!fp.rContainer) return;
        fp.rContainer.innerHTML = "";
        var container = fp.rContainer.appendChild(document.createElement("div"));
        container.className = "flatpickr-monthSelect-months";
        self.months = [];

        var months = (fp.l10n && fp.l10n.months && fp.l10n.months.longhand)
          ? fp.l10n.months.longhand
          : ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];

        if (config.shorthand && fp.l10n && fp.l10n.months && fp.l10n.months.shorthand) {
          months = fp.l10n.months.shorthand;
        }

        var selMonth = fp.selectedDates.length > 0 ? fp.selectedDates[0].getMonth() : fp.currentMonth;
        var selYear = fp.selectedDates.length > 0 ? fp.selectedDates[0].getFullYear() : fp.currentYear;

        for (var i = 0; i < 12; i++) {
          var cell = document.createElement("span");
          cell.className = "flatpickr-monthSelect-month";
          cell.textContent = months[i];
          cell.month = i;

          if (selYear === fp.currentYear && selMonth === i) {
            cell.classList.add("selected");
          }

          (function (c) {
            c.addEventListener("click", function (e) {
              e.preventDefault();
              e.stopPropagation();
              selectMonth(c);
              fp.close();
            });
          })(cell);

          container.appendChild(cell);
          self.months.push(cell);
        }
      }

      return {
        onParseConfig: function () {
          fp.config.mode = "single";
          fp.config.enableTime = false;
        },
        onValueUpdate: function () {
          if (!fp.selectedDates.length) return;
          var date = fp.selectedDates[0];
          fp.currentMonth = date.getMonth();
          fp.currentYear = date.getFullYear();
        },
        onReady: [
          function () {
            fp.calendarContainer.classList.add("flatpickr-monthSelect");
            setupMonths();
          }
        ],
        onYearChange: [
          function () {
            setupMonths();
          }
        ],
        onOpen: [
          function () {
            setupMonths();
          }
        ]
      };
    };
  }

  return monthSelectPlugin;
}));
