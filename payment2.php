<button id="payBtn">Payer</button>
<div id="result"></div>

<?php $src="https://www.paypal.com/sdk/js?client-id=AfRHvfTKMPukO7lcCx945HcARQGy39PoNPdrpaa5vamPn2PsyTR165QQ1gLDh9gx13BUWcKT3MWs74qc"; ?>

 <!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>Paiement PayPal</title>
  <script src="https://www.paypal.com/sdk/js?client-id=AfRHvfTKMPukO7lcCx945HcARQGy39PoNPdrpaa5vamPn2PsyTR165QQ1gLDh9gx13BUWcKT3MWs74qc&currency=EUR"></script>
</head>
<body>
  <h3>Paiement</h3>
  <div id="paypal-button-container"></div>

  <script>
    const AMOUNT = "15.00"; // juste pour affichage éventuel

    paypal.Buttons({
      createOrder: async function () {
        const res = await fetch('/paypal/create-order.php', { method: 'POST' });
        const data = await res.json(); // { id: "ORDER_ID" }
        return data.id;
      },

      onApprove: async function (data) {
        const res = await fetch('/paypal/capture-order.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ orderID: data.orderID })
        });
        const result = await res.json(); // { status: "...", capture: {...} }

        if (result.status === 'COMPLETED') {
          alert("Paiement réussi ✅");
          // ex: document.location.href="/success.php";
        } else {
          alert("Paiement non complété.");
          // ex: document.location.href="/cancel.php";
        }

        console.log(result);
      },

      onError: function (err) {
        console.error(err);
        alert("Erreur pendant le paiement.");
      }
    }).render('#paypal-button-container');
  </script>
</body>
</html>