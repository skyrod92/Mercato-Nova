// Interception des formulaires d'ajout (Panier/Favoris)
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (this.action.includes('add_cart.php') || this.action.includes('toggle_favorite.php')) {
            e.preventDefault();
            fetch(this.action, { method: 'POST', body: new FormData(this) }).then(() => {
                const countSpan = document.querySelector('nav a[href="panier.php"] strong');
                if (countSpan && this.action.includes('add_cart.php')) {
                    countSpan.textContent = parseInt(countSpan.textContent) + 1;
                }
                if (this.querySelector('.favorite-btn')) {
                    const btn = this.querySelector('.favorite-btn');
                    btn.textContent = btn.textContent.includes('Retirer') ? '♡ Ajouter aux favoris' : '♥ Retirer des favoris';
                }
            });
        }
    });
});

function updateCart(productId, quantity) {
    fetch('actions/update_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}&quantity=${quantity}`
    }).then(() => {
        calculateTotal();
        updateHeaderCount();
    });
}

function removeItem(productId) {
    fetch('actions/update_cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `product_id=${productId}&quantity=0`
    }).then(response => {
        if (response.ok) {
            const row = document.getElementById('product-row-' + productId);
            if (row) row.remove();
            calculateTotal();
            updateHeaderCount();
        }
    });
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.cart-row').forEach(row => {
        const price = parseFloat(row.dataset.price);
        const qty = parseInt(row.querySelector('input[type="number"]').value);
        total += price * qty;
    });
    document.querySelector('.total').textContent = 'Total : ' + total.toFixed(2).replace('.', ',') + ' €';
}

function updateHeaderCount() {
    let totalQty = 0;
    document.querySelectorAll('.cart-row input[type="number"]').forEach(input => {
        totalQty += parseInt(input.value);
    });
    const countSpan = document.querySelector('nav a[href="panier.php"] strong');
    if (countSpan) {
        countSpan.textContent = totalQty;
    }
}

function placeBid(productId, form) {
    const formData = new FormData(form);
    const messageDiv = document.getElementById('bid-message');
    
    fetch('actions/place_bid.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data === "success") {
            messageDiv.style.color = "var(--success)";
            messageDiv.textContent = "Enchère validée !";
            form.reset(); // Vide le champ après succès
        } else {
            messageDiv.style.color = "var(--danger)";
            messageDiv.textContent = data; // Affiche le message "Misez au moins X€"
        }
    });
}
