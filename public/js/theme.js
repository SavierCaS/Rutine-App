/**
 * ==============================================================================
 * KYNAP FITNESS PLATFORM - GESTOR GLOBAL DE TEMA (DARK / LIGHT MODE)
 * ==============================================================================
 * Maneja la preferencia visual del usuario, persistencia en localStorage y
 * actualización reactiva del DOM sin parpadeo (Zero FOUC).
 *
 * @author Senior Full-Stack Engineer
 * ==============================================================================
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'kynap_theme';

  // 1. Obtener tema guardado o preferencia por defecto (dark)
  function getPreferredTheme() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'light' || saved === 'dark') {
      return saved;
    }
    // Si no hay preferencia guardada, verificar preferencia del sistema o usar dark por defecto
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
      return 'light';
    }
    return 'dark';
  }

  // 2. Aplicar tema inmediatamente al elemento raíz (HTML)
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    if (theme === 'light') {
      document.body?.classList.add('light-theme');
    } else {
      document.body?.classList.remove('light-theme');
    }
    updateThemeButtons(theme);
  }

  // 3. Actualizar iconos y etiquetas accesibles de los botones en la página
  function updateThemeButtons(theme) {
    const buttons = document.querySelectorAll('.btn-theme-toggle');
    buttons.forEach((btn) => {
      if (theme === 'light') {
        btn.innerHTML = '🌙';
        btn.setAttribute('title', 'Cambiar a modo oscuro');
        btn.setAttribute('aria-label', 'Cambiar a modo oscuro');
      } else {
        btn.innerHTML = '☀️';
        btn.setAttribute('title', 'Cambiar a modo claro');
        btn.setAttribute('aria-label', 'Cambiar a modo claro');
      }
    });
  }

  // 4. Función global para alternar entre temas
  window.toggleTheme = function () {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    const nextTheme = current === 'light' ? 'dark' : 'light';
    localStorage.setItem(STORAGE_KEY, nextTheme);
    applyTheme(nextTheme);

    // Notificar a componentes que puedan escuchar el cambio (ej. gráficos de canvas)
    window.dispatchEvent(new CustomEvent('kynapThemeChanged', { detail: { theme: nextTheme } }));
  };

  // Ejecución inmediata para prevenir flash de contenido sin estilos
  const initialTheme = getPreferredTheme();
  document.documentElement.setAttribute('data-theme', initialTheme);

  // Sincronizar iconos una vez cargado el DOM
  document.addEventListener('DOMContentLoaded', () => {
    applyTheme(initialTheme);
  });
})();
