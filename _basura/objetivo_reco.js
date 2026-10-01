async function getRecommendationsFromPanel(panelType) {
    const username = document.getElementById('username')?.textContent.trim();
    const userData = document.getElementById('userData');
    const objetivo = userData.getAttribute('data-objetivo');
    const actividad = userData.getAttribute('data-actividad');
    const agua = userData.getAttribute('data-agua');
    const peso = userData.getAttribute('data-peso');
    const altura = userData.getAttribute('data-altura');
    const edad = userData.getAttribute('data-edad');
    
    let prompt = '';

    switch(panelType) {
        case 'objetivo':
            prompt = `¿Cómo puedo ${objetivo}? si Actualmente realizo actividad física de nivel ${actividad}, consumo ${agua} litros de agua al día, mi peso es de ${peso} kg y mido ${altura} cm. ¿Qué recomendaciones me puedes dar para cumpir con mi objetivo de ${objetivo}?. (Responde como un experto nutricionista y habla de manera clara y sencilla para todas las edades, ademas debes comenzar el mensaje de esta manera: "Hola ${username}, soy Kynap AI". no debes usar negrita. El mensaje debe ser de maximo 200 palabras y usa emojis para empatizar con el lector. y debes despedirte del lector mandando este mensaje: "¡Te deseo grandes exito en el camino para lograr tu objetivo de Ponerte en forma!. Y recuerda, siempre estaré aquí para ti. Kynap AI 😉"). menciona de manera directa los datos que te he compartido`;
            break;
        case 'actividad':
            prompt = `¿Cómo puedo mejorar mi actividad física de nivel ${actividad} siendo que consumo ${agua} litros de agua al día, peso ${peso} kg y mido ${altura} cm? (Responde como un experto en nutrición y habla de manera clara y directa, ademas debes comenzar el mensaje de esta manera: "Hola ${username}, soy Kynap AI". no debes usar negrita. El mensaje debe ser de maximo 200 palabras y usa emojis para empatizar con el lector. y debes despedirte del lector mandando este mensaje: "¡Te deseo grandes exito en el camino para lograr tu objetivo de mejorar tu actividad fisica!. Y recuerda, siempre estaré aquí para ti. Kynap AI 😉"). menciona de manera directa los datos que te he compartido`;
            break;
        case 'agua':
            prompt = `¿Cómo puedo mejorar mi hidratación si actualmente consumo ${agua} litros de agua al día, peso ${peso} kg, mido ${altura} cm y realizo actividad física de nivel ${actividad}? (Responde como un experto en nutrición y habla de manera clara y directa, ademas debes comenzar el mensaje de esta manera: "Hola ${username}, soy Kynap AI". no debes usar negrita. El mensaje debe ser de maximo 200 palabras y usa emojis para empatizar con el lector. y debes despedirte del lector mandando este mensaje: "¡Te deseo grandes exito en el camino para lograr tu objetivo de mejorar tu hidratación!. Y recuerda, siempre estaré aquí para ti. Kynap AI 😉"). menciona de manera directa los datos que te he compartido`;
            break;
        case 'peso':
            prompt = `¿Tengo un buen peso? considerando que peso ${peso} kg, mido ${altura} cm y consumo ${agua} litros de agua al día? (Responde como un experto en nutrición y habla de manera clara y directa, ademas debes comenzar el mensaje de esta manera: "Hola ${username}, soy Kynap AI". no debes usar negrita. El mensaje debe ser de maximo 200 palabras y usa emojis para empatizar con el lector. y debes despedirte del lector mandando este mensaje: "¡Te deseo grandes exito en el camino para lograr tu objetivo de mejorar tu dieta y actividad fisica!. Y recuerda, siempre estaré aquí para ti. Kynap AI 😉"). menciona de manera directa los datos que te he compartido`;
            break;
        default:
            return;
    }

    Swal.fire({
        title: 'Cargando...',
        text: 'Kynap está preparando una recomendación para ti 😉',
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const response = await fetch('kynap_ai.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ message: prompt })
    });

    const data = await response.json();

    if (data.response) {
        Swal.fire({
            title: 'Redirigiendo...',
            text: 'Te estamos enviando con Kynap AI...',
            showConfirmButton: false,
            timer: 5000,
            icon: 'info'
        }).then(() => {
            sessionStorage.setItem('kynap_mensaje', data.response);
            window.location.href = 'chat.php';
        });
    } else {
        Swal.fire({
            title: 'Error',
            text: 'Ha ocurrido un inconveniente con Kynap AI, lo solucionaremos en breve!',
            icon: 'error',
            confirmButtonText: 'Cerrar'
        });
    }
}
