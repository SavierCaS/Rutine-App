const sonidos = {
  "4-4-4": "",
  "4-7-8": "",
  "2-2": "",
  "5-5": ""
};

const rutinas = {
  "4-4-4": [4000, 4000, 4000, 4000],
  "4-7-8": [4000, 7000, 8000, 0],
  "2-2": [2000, 0, 2000, 0],
  "5-5": [5000, 0, 5000, 0]
};

const colores = {
  "4-4-4": "rgba(100, 149, 237, 0.3)",
  "4-7-8": "rgba(72, 61, 139, 0.3)",
  "2-2": "rgba(34, 139, 34, 0.3)",
  "5-5": "rgba(255, 140, 0, 0.3)"
};

let animacionActiva = false;
let circulo, mensajeTimeout;

function cambiarRutina() {
  const tipo = document.getElementById("tipoRespiracion").value;
  document.getElementById("breathAudio").src = sonidos[tipo];
}

function iniciarRespiracion() {
  if (animacionActiva) return;
  const tipo = document.getElementById("tipoRespiracion").value;
  const placeholder = document.getElementById("mentePlaceholder");
  const mensaje = document.getElementById("mensajeGuia");
  const audio = document.getElementById("breathAudio");

  placeholder.innerHTML = "";
  mensaje.textContent = "";

  circulo = document.createElement('div');
  circulo.className = 'circle';
  circulo.style.backgroundColor = colores[tipo];
  circulo.style.width = '50px';
  circulo.style.height = '50px';
  placeholder.appendChild(circulo);

  audio.src = sonidos[tipo];
  audio.play();

  const [inhala, mantén, exhala, espera] = rutinas[tipo];

  function ciclo() {
    mensaje.textContent = "Inhala...";
    circulo.style.transition = `transform ${inhala}ms ease-in-out`;
    circulo.style.transform = 'scale(2.5)';

    mensajeTimeout = setTimeout(() => {
      mensaje.textContent = "Mantén...";
      circulo.style.transition = `transform ${mantén}ms ease-in-out`;
      circulo.style.transform = 'scale(2.5)';

      mensajeTimeout = setTimeout(() => {
        mensaje.textContent = "Exhala...";
        circulo.style.transition = `transform ${exhala}ms ease-in-out`;
        circulo.style.transform = 'scale(1)';

        mensajeTimeout = setTimeout(() => {
          mensaje.textContent = "Espera...";
          mensajeTimeout = setTimeout(ciclo, espera);
        }, exhala);
      }, mantén);
    }, inhala);
  }

  ciclo();
  animacionActiva = true;
}

function pausarRespiracion() {
  if (!animacionActiva) return;
  clearTimeout(mensajeTimeout);
  const placeholder = document.getElementById("mentePlaceholder");
  const mensaje = document.getElementById("mensajeGuia");
  const audio = document.getElementById("breathAudio");
  placeholder.innerHTML = "Haz clic para comenzar";
  mensaje.textContent = "";
  audio.pause();
  audio.currentTime = 0;
  animacionActiva = false;
}

document.addEventListener("DOMContentLoaded", cambiarRutina);
