/**
 * @file
 * Masquerade Toolbar functionality.
 */

(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.masqueradeToolbar = {
    attach(context, settings) {
      const toolbars = once(
        'masquerade-toolbar',
        '.masquerade-toolbar',
        context,
      );

      if (!toolbars.length) {
        return;
      }

      toolbars.forEach((toolbar) => {
        const $toolbar = $(toolbar);
        const config = drupalSettings.masqueradeToolbar || {};
        const $toggleBtn = $toolbar.find('.masquerade-toolbar__toggle-btn');
        const $closeBtn = $toolbar.find('.masquerade-toolbar__close');
        const $searchInput = $toolbar.find('.masquerade-toolbar__search-input');
        const $autocomplete = $toolbar.find(
          '.masquerade-toolbar__autocomplete',
        );

        // Toggle toolbar.
        $toggleBtn.on('click', () => {
          $toolbar.toggleClass('masquerade-toolbar--expanded');
        });

        // Close toolbar.
        $closeBtn.on('click', () => {
          $toolbar.removeClass('masquerade-toolbar--expanded');
        });

        // Keyboard shortcut (Alt+M).
        if (config.keyboardShortcuts) {
          $(document).on('keydown', (e) => {
            if (e.altKey && e.key === 'm') {
              e.preventDefault();
              $toolbar.toggleClass('masquerade-toolbar--expanded');
              if ($toolbar.hasClass('masquerade-toolbar--expanded')) {
                $searchInput.focus();
              }
            }
          });
        }

        // Autocomplete.
        let autocompleteTimeout;
        $searchInput.on('input', function () {
          clearTimeout(autocompleteTimeout);
          const query = this.value;

          if (query.length < 2) {
            $autocomplete.hide().empty();
            return;
          }

          autocompleteTimeout = setTimeout(() => {
            $.ajax({
              url: '/masquerade-toolbar/autocomplete',
              data: { q: query },
              success(data) {
                $autocomplete.empty();

                if (data.length === 0) {
                  $autocomplete.hide();
                  return;
                }

                data.forEach((user) => {
                  const $item = $(
                    '<div class="masquerade-toolbar__autocomplete-item"></div>',
                  );
                  $item[0].textContent = user.label;
                  $item
                    .data('uid', user.uid)
                    .data('url', user.url)
                    .on('click', () => {
                      window.location.href = user.url;
                    });
                  $autocomplete.append($item);
                });

                $autocomplete.show();
              },
            });
          }, 300);
        });

        // Click outside to close autocomplete.
        $(document).on('click', (e) => {
          if (!$(e.target).closest('.masquerade-toolbar__search').length) {
            $autocomplete.hide();
          }
        });

        // Set initial state.
        if (config.collapsed) {
          $toolbar.removeClass('masquerade-toolbar--expanded');
        } else {
          $toolbar.addClass('masquerade-toolbar--expanded');
        }

        // Mobile hide.
        if (config.mobileHide) {
          const checkMobile = () => {
            if (window.innerWidth < 768) {
              $toolbar.addClass('masquerade-toolbar--mobile-hidden');
            } else {
              $toolbar.removeClass('masquerade-toolbar--mobile-hidden');
            }
          };
          checkMobile();
          $(window).on('resize', checkMobile);
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings, once);