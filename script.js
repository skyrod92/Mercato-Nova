// ==========================================
// 1. MODULE INTERCEPTIONS FORMULAIRES GLOBALES
// ==========================================
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const actionUrl = this.getAttribute('action') || '';
        const isCart = actionUrl.includes('add_cart.php');
        const isFav = actionUrl.includes('toggle_favorite.php');

        // On n'intercepte que le panier et les favoris, le reste passe normalement
        if (isCart || isFav) {
            e.preventDefault();
            e.stopImmediatePropagation(); // Empêche strictement le double ajout concurrent (2 par 2)

            fetch(actionUrl, { 
                method: 'POST', 
                body: new FormData(this) 
            })
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau');
                return response.text();
            })
            .then(() => {
                if (isCart) {
                    const countSpan = document.querySelector('nav a[href="panier.php"] strong');
                    if (countSpan) {
                        countSpan.textContent = parseInt(countSpan.textContent || 0) + 1;
                    }
                }
                if (isFav) {
                    if (window.location.pathname.includes('favoris.php')) {
                        location.reload();
                    } else {
                        const btn = this.querySelector('.favorite-btn');
                        if (btn) {
                            btn.textContent = btn.textContent.includes('Retirer') ? '♡ Ajouter aux favoris' : '♥ Retirer des favoris';
                        }
                    }
                }
            })
            .catch(err => console.error(err));
        }
    });
});

// Arrondi automatique des centimes au changement de focus
const bidInput = document.getElementById('bid-amount-input');
if (bidInput) {
    bidInput.addEventListener('blur', function() {
        if (this.value) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });
}

// ==========================================
// 2. LIAISONS SÉCURISÉES DES ACTIONS ENCHÈRES
// ==========================================
document.addEventListener("DOMContentLoaded", function() {
    
    // Ouverture modale
    const btnAutoBid = document.getElementById('btn-auto-bid');
    const modal = document.getElementById('autoBidModal');
    if (btnAutoBid && modal) {
        btnAutoBid.addEventListener('click', function() {
            modal.style.display = 'flex';
        });
    }

    // Fermeture modale
    const btnCloseModal = document.getElementById('btn-close-modal');
    if (btnCloseModal && modal) {
        btnCloseModal.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    // Retrait d'enchère unifié (Page produit + Page mes enchères)
    const btnRetirerMise = document.getElementById('btn-retirer-mise');
    if (btnRetirerMise) {
        btnRetirerMise.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            if (!productId) return;

            if (!confirm("Êtes-vous sûr de vouloir retirer votre dernière offre sur ce produit ?")) return;
            
            const formData = new FormData();
            formData.append('product_id', productId);

            fetch('actions/delete_bid.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "success") {
                    location.reload();
                } else {
                    alert(data);
                }
            });
        });
    }
});

// Envoi manuel d'une proposition d'enchère
function placeBid(productId, form) {
    const formData = new FormData(form);
    const messageDiv = document.getElementById('bid-message');
    if (!messageDiv) return;
    
    fetch('actions/place_bid.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.trim() === "success") {
            messageDiv.style.color = "#2ecc71";
            messageDiv.textContent = "Enchère validée ! Actualisation...";
            form.reset();
            setTimeout(() => { location.reload(); }, 1000);
        } else {
            messageDiv.style.color = "#e74c3c";
            messageDiv.textContent = data;
        }
    });
}

// ==========================================
// 3. LOGIQUE PANIER INTERNE (MODIFICATION QUANTITÉS)
// ==========================================
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
    const totalDiv = document.querySelector('.total');
    if (totalDiv) {
        totalDiv.textContent = 'Total : ' + total.toFixed(2).replace('.', ',') + ' €';
    }
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
// Effet pression boutons
 document.querySelectorAll('button').forEach(button => {
  button.addEventListener('mousedown', () => button.style.transform = 'scale(.98)');
  button.addEventListener('mouseup', () => button.style.transform = '');
});

// Barre de temps enchère
 document.querySelectorAll('.time-bar-fill').forEach(bar => {
    const startDate = new Date(bar.dataset.start).getTime();
    const endDate = new Date(bar.dataset.end).getTime();
    function updateBar() {
        const now = new Date().getTime();
        const total = endDate - startDate;
        const passed = now - startDate;
        let percent = (passed / total) * 100;
        if (percent < 0) percent = 0;
        if (percent > 100) percent = 100;
        bar.style.width = percent + "%";
    }
    if (!isNaN(startDate) && !isNaN(endDate)) {
      updateBar(); setInterval(updateBar, 1000);
    }
});

// Timer enchère
 document.querySelectorAll('.timer').forEach(timer => {
    const endDate = new Date(timer.dataset.end).getTime();
    function updateTimer() {
        const now = new Date().getTime();
        let diff = endDate - now;
        if (diff <= 0 || isNaN(diff)) { timer.textContent = "Terminé"; return; }
        const hours = Math.floor(diff / (1000 * 60 * 60));
        diff %= 1000 * 60 * 60;
        const minutes = Math.floor(diff / (1000 * 60));
        diff %= 1000 * 60;
        const seconds = Math.floor(diff / 1000);
        timer.textContent = String(hours).padStart(2, "0") + ":" + String(minutes).padStart(2, "0") + ":" + String(seconds).padStart(2, "0");
    }
    updateTimer(); setInterval(updateTimer, 1000);
});

function retirerEnchere(productId) {
    if (!confirm("Retirer votre dernière enchère sur ce produit ?")) return;
    const formData = new FormData();
    formData.append('product_id', productId);
    fetch('actions/delete_bid.php', { method: 'POST', body: formData })
      .then(res => res.text())
      .then(data => {
        if (data.trim() === 'success') location.reload();
        else alert(data);
      });
}
