jQuery(document).ready(function($){
	// Custom jQuery goes here

	//whatsapp order button
	let btnBuyNow = document.getElementById('btnBuyNow'),
		product_url = window.location,
		variation,
		select_input,
		redirect_link

	btnBuyNow.addEventListener('click', (e) => {
		e.preventDefault()

		redirect_link = `https://api.whatsapp.com/send?phone=60189686774&text=*(COD)%20Syah%26Co.%20Customer%20Detail*%0A%0AP%E2%AD%95STAGE%20Detail%20%3A%0AProduct%3A%20${product_url}` 
		variation = []
		select_input = document.querySelectorAll('.variations tbody tr td[class="value"] select')
		
		Array.from(select_input).forEach(select => {
			variation.push({
				attr: select.id.charAt(0).toUpperCase() + select.id.slice(1),
				val: select.value
			})
		})

		console.log(variation)
	
		variation.forEach(detail => {
			redirect_link += `%0A${detail.attr}%3A%20${detail.val}`
		})

		window.open(redirect_link)
	})
});
