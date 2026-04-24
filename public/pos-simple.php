<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>POS Simple</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .products { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; }
        .product { background: #f0f0f0; padding: 10px; cursor: pointer; }
        .cart { border: 1px solid #ccc; padding: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Point de Vente - Test</h1>
    <div class="products">
        <div class="product" data-price="2500" data-name="Filtre à huile">Filtre à huile - 2500 G</div>
        <div class="product" data-price="3500" data-name="Filtre à air">Filtre à air - 3500 G</div>
        <div class="product" data-price="5500" data-name="Plaquettes">Plaquettes - 5500 G</div>
    </div>
    <div class="cart" id="cart">Panier vide</div>
    <div>Total: <span id="total">0</span> G</div>
    <button onclick="pay()">Payer</button>
    
    <script>
        let cart = [];
        document.querySelectorAll('.product').forEach(p => {
            p.onclick = () => {
                let name = p.dataset.name;
                let price = parseInt(p.dataset.price);
                let existing = cart.find(i => i.name === name);
                if(existing) existing.qty++;
                else cart.push({name, price, qty:1});
                updateCart();
            };
        });
        
        function updateCart() {
            let html = '';
            let total = 0;
            cart.forEach(i => {
                total += i.price * i.qty;
                html += `<div>${i.name} x ${i.qty} = ${i.price * i.qty} G</div>`;
            });
            document.getElementById('cart').innerHTML = html || 'Panier vide';
            document.getElementById('total').innerHTML = total;
        }
        
        function pay() {
            alert('Vente effectuée ! Total: ' + document.getElementById('total').innerHTML + ' G');
            cart = [];
            updateCart();
        }
    </script>
</body>
</html>