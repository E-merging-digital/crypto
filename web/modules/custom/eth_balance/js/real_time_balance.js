(function (Drupal, once) {
  Drupal.behaviors.ethBalanceRealTime = {
    attach(context, settings) {
      // 'once' est la fonction globale fournie par core/once :contentReference[oaicite:0]{index=0}
      once('ethBalanceRealTime', '.eth-balance-value', context).forEach(el => {
        const endpoint = el.dataset.endpoint;
        const update = async () => {
          try {
            const resp = await fetch(endpoint);
            const json = await resp.json();
            if (json.balance !== undefined) {
              el.textContent = `${json.balance} ETH`;
            }
          }
          catch (err) {
            console.error('Erreur mise à jour solde ETH :', err);
          }
        };
        update();
        setInterval(update, 30000);
      });
    }
  };
})(Drupal, once);
