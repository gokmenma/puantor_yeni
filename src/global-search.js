/**
 * Global Topbar Search Engine (Personel, Proje, Kasa & Cari, İcra, İzin, Görev)
 * Puantor UI/UX Design System
 */
(function ($) {
    'use strict';

    var GlobalSearch = {
        input: null,
        clearBtn: null,
        spinner: null,
        dropdown: null,
        resultsContainer: null,
        categoriesContainer: null,
        footerInfo: null,
        totalCountBadge: null,

        debounceTimer: null,
        auditTimer: null,
        activeRequest: null,
        activeCategory: 'all',
        currentQuery: '',
        lastData: null,
        pendingAudit: null,
        selectedIndex: -1,
        totalVisibleItems: 0,
        isOpen: false,

        init: function () {
            this.input = $('#global-search-input');
            if (!this.input.length) return;

            this.clearBtn = $('#global-search-clear');
            this.spinner = $('#global-search-spinner');
            this.dropdown = $('#global-search-dropdown');
            this.resultsContainer = $('#global-search-results');
            this.categoriesContainer = $('#global-search-categories');
            this.footerInfo = $('#gs-footer-info');
            this.totalCountBadge = $('#gs-total-count');

            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            // Global Keyboard Shortcut: Ctrl+K / Cmd+K
            $(document).on('keydown', function (e) {
                var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
                var isCmdOrCtrl = isMac ? e.metaKey : e.ctrlKey;

                if (isCmdOrCtrl && (e.key === 'k' || e.key === 'K')) {
                    e.preventDefault();
                    self.input.focus();
                    self.input.select();
                    if (self.input.val().trim().length >= 1) {
                        self.openDropdown();
                    } else {
                        self.renderInitialSuggestions();
                    }
                } else if (e.key === 'Escape' && self.isOpen) {
                    e.preventDefault();
                    self.closeDropdown();
                    self.input.blur();
                }
            });

            // Input Events
            this.input.on('focus', function () {
                var val = $(this).val().trim();
                if (val.length >= 1) {
                    if (self.lastData && self.currentQuery === val) {
                        self.openDropdown();
                    } else {
                        self.triggerSearch(val);
                    }
                } else {
                    self.renderInitialSuggestions();
                }
            });

            this.input.on('input', function () {
                var val = $(this).val().trim();
                clearTimeout(self.auditTimer);
                if (val.length > 0) {
                    self.clearBtn.show();
                } else {
                    self.clearBtn.hide();
                }

                clearTimeout(self.debounceTimer);
                if (val.length >= 1) {
                    self.debounceTimer = setTimeout(function () {
                        self.triggerSearch(val);
                    }, 220);
                } else {
                    self.renderInitialSuggestions();
                }
            });

            // Keyboard Navigation inside input
            this.input.on('keydown', function (e) {
                if (!self.isOpen) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    self.moveSelection(1);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    self.moveSelection(-1);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    self.activateSelected();
                }
            });

            // Clear Button
            this.clearBtn.on('click', function (e) {
                e.stopPropagation();
                self.flushSearchAudit('cleared');
                self.input.val('').focus();
                self.clearBtn.hide();
                self.renderInitialSuggestions();
            });

            // Category Tab Clicks
            this.categoriesContainer.on('click', '.gs-cat-pill', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var cat = $(this).data('cat');
                self.setCategory(cat);
            });

            // Click outside to close
            $(document).on('click', function (e) {
                if (!$(e.target).closest('#global-search-container').length) {
                    self.closeDropdown();
                }
            });

            $(window).on('pagehide', function () {
                self.flushSearchAudit('closed');
            });

            // Prevent closing when clicking inside dropdown
            this.dropdown.on('click', function (e) {
                e.stopPropagation();
            });

            // Click on result item
            this.resultsContainer.on('click', '.gs-result-item', function () {
                var itemType = $(this).data('type');
                var itemId = $(this).data('id');
                self.flushSearchAudit('result_selected', itemType, itemId);
            });

            // Mouse hover on result items
            this.resultsContainer.on('mouseenter', '.gs-result-item', function () {
                var index = $(this).data('index');
                if (typeof index !== 'undefined') {
                    self.setSelectedIndex(index);
                }
            });
        },

        openDropdown: function () {
            this.dropdown.addClass('show');
            this.isOpen = true;
        },

        closeDropdown: function () {
            this.flushSearchAudit('closed');
            if (this.activeRequest) {
                this.activeRequest.abort();
                this.activeRequest = null;
            }
            this.dropdown.removeClass('show');
            this.isOpen = false;
            this.selectedIndex = -1;
        },

        setCategory: function (cat) {
            this.activeCategory = cat;
            if (this.pendingAudit) {
                this.pendingAudit.category = cat;
            }
            this.categoriesContainer.find('.gs-cat-pill').removeClass('active');
            this.categoriesContainer.find('.gs-cat-pill[data-cat="' + cat + '"]').addClass('active');

            if (this.lastData) {
                this.renderResults(this.lastData);
            }
        },

        triggerSearch: function (query) {
            var self = this;
            if (this.activeRequest) {
                this.activeRequest.abort();
            }
            this.currentQuery = query;
            this.spinner.show();
            this.clearBtn.hide();

            this.activeRequest = $.ajax({
                url: 'api/global_search.php',
                type: 'GET',
                dataType: 'json',
                data: {
                    q: query,
                    category: 'all',
                    limit: 8
                },
                success: function (res) {
                    self.activeRequest = null;
                    self.spinner.hide();
                    if (self.input.val().trim().length > 0) {
                        self.clearBtn.show();
                    }

                    if (res && res.status === 'success') {
                        self.lastData = res;
                        self.pendingAudit = {
                            query: query,
                            category: self.activeCategory,
                            totalFound: res.counts ? (res.counts.all || 0) : 0
                        };
                        clearTimeout(self.auditTimer);
                        self.auditTimer = setTimeout(function () {
                            self.flushSearchAudit('completed');
                        }, 1500);
                        self.updateCounters(res.counts);
                        self.renderResults(res);
                        self.openDropdown();
                    }
                },
                error: function (xhr, status) {
                    self.activeRequest = null;
                    if (status === 'abort') return;
                    self.spinner.hide();
                    if (self.input.val().trim().length > 0) {
                        self.clearBtn.show();
                    }
                }
            });
        },

        flushSearchAudit: function (completionType, selectedType, selectedId) {
            clearTimeout(this.auditTimer);
            this.auditTimer = null;
            if (!this.pendingAudit || this.pendingAudit.query.length < 2) return;

            var audit = this.pendingAudit;
            this.pendingAudit = null;
            var formData = new FormData();
            formData.append('action', 'log_search');
            formData.append('query', audit.query);
            formData.append('category', audit.category);
            formData.append('total_found', audit.totalFound);
            formData.append('completion_type', completionType || 'closed');
            if (selectedType) formData.append('selected_type', selectedType);
            if (typeof selectedId !== 'undefined') formData.append('selected_id', selectedId);

            if (navigator.sendBeacon) {
                navigator.sendBeacon('api/global_search.php', formData);
                return;
            }

            $.ajax({
                url: 'api/global_search.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                global: false
            });
        },

        updateCounters: function (counts) {
            if (!counts) return;
            $('#count-all').text(counts.all || 0);
            $('#count-persons').text(counts.persons || 0);
            $('#count-projects').text(counts.projects || 0);
            $('#count-financial').text(counts.financial || 0);
            $('#count-icra').text(counts.icra || 0);
            $('#count-izin').text(counts.izin || 0);
            $('#count-tasks').text(counts.tasks || 0);
            this.totalCountBadge.text(counts.all || 0);
        },

        highlightText: function (text, query) {
            if (!text) return '';
            if (!query) return $('<div>').text(text).html();

            var safeText = $('<div>').text(text).html();
            var escapedQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            var regex = new RegExp('(' + escapedQuery + ')', 'gi');
            return safeText.replace(regex, '<mark class="gs-highlight">$1</mark>');
        },

        renderResults: function (data) {
            var self = this;
            var html = '';
            var itemGlobalIndex = 0;
            var cat = this.activeCategory;
            var query = this.currentQuery;
            var results = data.results || {};

            var moduleConfig = {
                persons:   { title: 'PERSONELLER', icon: 'ti ti-users', color: 'blue', items: results.persons || [] },
                projects:  { title: 'PROJELER', icon: 'ti ti-folders', color: 'purple', items: results.projects || [] },
                financial: { title: 'KASA & CARİ FİRMALAR', icon: 'ti ti-wallet', color: 'emerald', items: results.financial || [] },
                icra:      { title: 'İCRA DOSYALARI', icon: 'ti ti-scale', color: 'amber', items: results.icra || [] },
                izin:      { title: 'İZİN TALEPLERİ', icon: 'ti ti-calendar-time', color: 'indigo', items: results.izin || [] },
                tasks:     { title: 'GÖREVLER', icon: 'ti ti-checkbox', color: 'teal', items: results.tasks || [] }
            };

            var modulesToRender = [];
            if (cat === 'all') {
                modulesToRender = ['persons', 'projects', 'financial', 'icra', 'izin', 'tasks'];
            } else if (moduleConfig[cat]) {
                modulesToRender = [cat];
            }

            var totalCount = 0;
            modulesToRender.forEach(function (mKey) {
                var mod = moduleConfig[mKey];
                if (mod && mod.items && mod.items.length > 0) {
                    totalCount += mod.items.length;
                    html += '<div class="gs-category-group">';
                    html += '  <div class="gs-category-header">';
                    html += '    <span class="gs-cat-title"><i class="' + mod.icon + '"></i> ' + mod.title + '</span>';
                    html += '    <span class="gs-cat-count-badge">' + mod.items.length + '</span>';
                    html += '  </div>';
                    html += '  <div class="gs-items-list">';

                    mod.items.forEach(function (item) {
                        var isFirst = (itemGlobalIndex === 0);
                        var activeClass = isFirst ? 'active' : '';

                        html += '<a href="' + item.url + '" class="gs-result-item ' + activeClass + '" data-index="' + itemGlobalIndex + '" data-type="' + item.type + '" data-id="' + item.id + '">';
                        
                        // Left Avatar / Badge
                        html += '  <div class="gs-item-avatar gs-avatar-' + item.color_theme + '">';
                        html += '    <span>' + item.initial + '</span>';
                        html += '    <span class="gs-avatar-dot"></span>';
                        html += '  </div>';

                        // Middle Content
                        html += '  <div class="gs-item-content">';
                        html += '    <div class="gs-item-row-primary">';
                        html += '      <span class="gs-item-title">' + self.highlightText(item.title, query) + '</span>';
                        if (item.extra_info) {
                            html += '      <span class="gs-item-extra">' + self.highlightText(item.extra_info, query) + '</span>';
                        }
                        if (item.date) {
                            html += '      <span class="gs-item-date"><i class="ti ti-calendar"></i> ' + item.date + '</span>';
                        }
                        html += '    </div>';

                        html += '    <div class="gs-item-row-secondary">';
                        html += '      <span class="gs-item-subtitle">' + self.highlightText(item.subtitle, query) + '</span>';
                        html += '    </div>';
                        html += '  </div>';

                        // Right Status & Chevron
                        html += '  <div class="gs-item-actions">';
                        if (item.badge) {
                            html += '    <span class="gs-status-pill ' + item.badge_class + '">' + item.badge + '</span>';
                        }
                        html += '    <i class="ti ti-chevron-right gs-item-arrow"></i>';
                        html += '  </div>';

                        html += '</a>';
                        itemGlobalIndex++;
                    });

                    html += '  </div>';
                    html += '</div>';
                }
            });

            this.totalVisibleItems = itemGlobalIndex;
            this.selectedIndex = itemGlobalIndex > 0 ? 0 : -1;

            if (totalCount === 0) {
                html = '<div class="gs-empty-state">';
                html += '  <div class="gs-empty-icon"><i class="ti ti-search"></i></div>';
                html += '  <div class="gs-empty-title">"' + $('<div>').text(query).html() + '" ile eşleşen kayıt bulunamadı</div>';
                html += '  <div class="gs-empty-subtitle">Farklı bir personel adı, TC No, proje, kasa veya görev deneyebilirsiniz.</div>';
                html += '</div>';
            }

            this.resultsContainer.html(html);
            this.totalCountBadge.text(data.counts ? (cat === 'all' ? data.counts.all : (data.counts[cat] || 0)) : totalCount);
        },

        renderInitialSuggestions: function () {
            var html = '<div class="gs-suggestions-wrap">';
            html += '  <div class="gs-suggestions-header">Hızlı Modül Sayfaları</div>';
            html += '  <div class="gs-suggestions-grid">';
            html += '    <a href="/personeller" class="gs-suggestion-card"><i class="ti ti-users text-blue"></i><span>Personeller</span></a>';
            html += '    <a href="/projeler" class="gs-suggestion-card"><i class="ti ti-folders text-purple"></i><span>Projeler</span></a>';
            html += '    <a href="/puantaj" class="gs-suggestion-card"><i class="ti ti-calendar-month text-azure"></i><span>Puantaj</span></a>';
            html += '    <a href="/bordro" class="gs-suggestion-card"><i class="ti ti-calculator text-emerald"></i><span>Bordro</span></a>';
            html += '    <a href="/gelir-gider-islemleri" class="gs-suggestion-card"><i class="ti ti-wallet text-teal"></i><span>Kasa Hareketleri</span></a>';
            html += '    <a href="/personel-icra-dosyalari" class="gs-suggestion-card"><i class="ti ti-scale text-amber"></i><span>İcra Dosyaları</span></a>';
            html += '    <a href="/izin-talepleri" class="gs-suggestion-card"><i class="ti ti-calendar-time text-indigo"></i><span>İzin Talepleri</span></a>';
            html += '    <a href="/gorevler" class="gs-suggestion-card"><i class="ti ti-checkbox text-orange"></i><span>Görevler</span></a>';
            html += '  </div>';
            html += '</div>';

            this.resultsContainer.html(html);
            this.updateCounters({ all: 0, persons: 0, projects: 0, financial: 0, icra: 0, izin: 0, tasks: 0 });
            this.totalVisibleItems = 0;
            this.selectedIndex = -1;
            this.openDropdown();
        },

        moveSelection: function (step) {
            if (this.totalVisibleItems <= 0) return;

            var newIndex = this.selectedIndex + step;
            if (newIndex < 0) {
                newIndex = this.totalVisibleItems - 1;
            } else if (newIndex >= this.totalVisibleItems) {
                newIndex = 0;
            }

            this.setSelectedIndex(newIndex);
        },

        setSelectedIndex: function (index) {
            this.selectedIndex = index;
            var items = this.resultsContainer.find('.gs-result-item');
            items.removeClass('active');

            var target = items.filter('[data-index="' + index + '"]');
            if (target.length) {
                target.addClass('active');
                var container = this.resultsContainer;
                var targetTop = target.position().top;
                var targetBottom = targetTop + target.outerHeight();
                var containerHeight = container.height();

                if (targetBottom > containerHeight) {
                    container.scrollTop(container.scrollTop() + (targetBottom - containerHeight) + 10);
                } else if (targetTop < 0) {
                    container.scrollTop(container.scrollTop() + targetTop - 10);
                }
            }
        },

        activateSelected: function () {
            if (this.selectedIndex >= 0) {
                var target = this.resultsContainer.find('.gs-result-item[data-index="' + this.selectedIndex + '"]');
                if (target.length) {
                    var itemType = target.data('type');
                    var itemId = target.data('id');
                    this.flushSearchAudit('result_selected', itemType, itemId);

                    if (target.attr('href')) {
                        window.location.href = target.attr('href');
                    }
                }
            }
        }
    };

    $(document).ready(function () {
        GlobalSearch.init();
    });

})(jQuery);
