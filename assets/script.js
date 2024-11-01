document.addEventListener('alpine:init', () => {
  // console.log('fdfd')
  Alpine.data('todo', () => ({
    checked: false,

    toggle(e) {
      var $this = this;

      // Toggle the checkbox value
      this.checked = !this.checked;

      // Get nonce value from the DOM
      const nonce = document.querySelector('#tdb_nonce_input_id').value;
      const post_id = document.querySelector('#tdb_post_id').value;

      window.wp.ajax.post( 'update_checkbox_state', {
        checked: !$this.checked,
        value: e.target.value,
        post_id: post_id,
        nonce: nonce // Include nonce in the request
      })
      .done(function(response) {
        $this.checked = !$this.checked;
      })

      // Send a request to the backend
      // fetch('/your-backend-endpoint', {
      //   method: 'POST', // or 'PUT', 'DELETE', etc. depending on your backend
      //   headers: {
      //     'Content-Type': 'application/json',
      //     // Add any other headers if required
      //   },
      //   body: JSON.stringify({
      //     action: 'update_checkbox_state', // Action hook name
      //     data: {
      //       checked: this.checked,
      //       nonce: nonce // Include nonce in the request
      //     }
      //   }),
      // })
      //   .then(response => {
      //     if (!response.ok) {
      //       throw new Error('Network response was not ok');
      //     }
      //     // Handle successful response
      //     console.log('Checkbox state sent to backend successfully');
      //   })
      //   .catch(error => {
      //     // Handle error
      //     console.error('There was a problem sending checkbox state to the backend:', error);
      //   });
    }
  }))

});