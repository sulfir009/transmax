$('.bus_img').slick({
    dots: true,
    arrows: true,
    infinite: true,
    autoplay: true,
    speed: 300,
    slidesToShow: 1,
    adaptiveHeight: true
    /*responsive: [
        {
            breakpoint: 767,
            settings: {
                arrows: false,
            }
        }
    ]*/
});
const overlay = document.getElementById('popup-regular');

$('[data-open-popup-regular]').on('click', function (){
    const popup = document.getElementById('popup-regular');
    popup.style.display = 'flex';

    document.getElementById('step-country').style.display = 'block';
    document.getElementById('step-country').classList.add('show');
});

overlay.addEventListener('click', function(event) {
    if (event.target === overlay) {
        closePopup();
    }
});


function closePopup() {
    document.getElementById('popup-regular').style.display = 'none';
}
