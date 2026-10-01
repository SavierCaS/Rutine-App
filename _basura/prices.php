<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Suscripciones</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Arial', sans-serif;
      background: #f4f7fa;
      color: #333;
      padding: 40px 15px;
    }

    .pricing-section {
      max-width: 1200px;
      margin: auto;
      text-align: center;
    }

    .section-title {
      font-size: 2.8rem;
      margin-bottom: 40px;
      font-weight: bold;
      color: #222;
    }

    .pricing-cards {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
    }

    .pricing-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      padding: 30px 20px;
      width: 100%;
      max-width: 320px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .pricing-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
    }

    .plan-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1e88e5;
      margin-bottom: 10px;
    }

    .plan-price {
      font-size: 2.5rem;
      color: #222;
      margin-bottom: 10px;
    }

    .plan-description {
      font-size: 1rem;
      color: #666;
      margin-bottom: 20px;
    }

    .plan-features {
      list-style: none;
      padding: 0;
      margin-bottom: 25px;
      text-align: left;
    }

    .plan-features li {
      margin-bottom: 10px;
      padding-left: 25px;
      position: relative;
      font-size: 0.95rem;
    }

    .plan-features li::before {
      content: "✔";
      color: #4caf50;
      position: absolute;
      left: 0;
    }

    .btn {
      display: inline-block;
      padding: 10px 20px;
      background: #1e88e5;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      text-decoration: none;
      transition: background 0.3s ease;
    }

    .btn:hover {
      background: #1669bb;
    }

    .featured {
      border: 2px solid #ff9800;
    }
    @media (max-width: 768px) {
      .pricing-cards {
        flex-direction: column;
        align-items: center;
      }
    }

    #messageLimitOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
}

.overlay-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    text-align: center;
}

button {
    padding: 10px 20px;
    background: #1e88e5;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

button:hover {
    background: #1669bb;
}

  </style>
</head>
<body>
  <section class="pricing-section" id="pricing">
    <h2 class="section-title animate__animated animate__fadeInUp">Suscripciones</h2>
    <div class="pricing-cards">
      <div class="pricing-card animate__animated animate__fadeInUp animate__delay-1s">
        <h3 class="plan-title">Semanal</h3>
        <p class="plan-price">$4.000</p>
        <p class="plan-description">Acceso limitado a contenidos diarios de forma sustancial</p>
        <ul class="plan-features">
          <li>Recetas saludables</li>
          <li>Acceso básico</li>
          <li>Consejos de calidad</li>
          <li>Soporte semanal</li>
          <li>Actualizaciones cada 7 días</li>
        </ul>
        <a href="" class="btn">Adquirir</a>
      </div>

      <div class="pricing-card featured animate__animated animate__fadeInUp animate__delay-2s">
        <h3 class="plan-title">Mensual</h3>
        <p class="plan-price">$10.000</p>
        <p class="plan-description">Acceso extendido a planes nutricionales y ejercicios`</p>
        <ul class="plan-features">
          <li>Videos de recetas</li>
          <li>Contenido premium</li>
          <li>Seguimiento de hábitos</li>
          <li>Descarga de menús</li>
          <li>Asistencia prioritaria</li>
        </ul>
        <a href="" class="btn">Adquirir</a>
      </div>

      <div class="pricing-card animate__animated animate__fadeInUp animate__delay-3s">
        <h3 class="plan-title">Anual</h3>
        <p class="plan-price">$90.000</p>
        <p class="plan-description">Acceso completo e ilimitado a todos los recursos saludables</p>
        <ul class="plan-features">
          <li>Todo incluido</li>
          <li>Contenido exclusivo</li>
          <li>Seguimiento sin límites</li>
          <li>Plan VIP personalizado</li>
          <li>Bonos y desafíos mensuales</li>
        </ul>
        <a href="" class="btn">Adquirir</a>
      </div>
    </div>
  </section>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      console.log("Pricing panel loaded");
    });
  </script>
</body>
</html>
