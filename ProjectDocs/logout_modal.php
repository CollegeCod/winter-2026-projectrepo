<?php
// logout-modal.php
// Include this file on any page where you want the logout button to work:
//   <?php include 'logout_modal.php'; >
//
// Then add data-logout to any button or link to trigger the modal:
//   <button data-logout>Sign Out</button>
?>

<!-- !! Replace with your actual stylesheet name !! -->
<link rel="stylesheet" href="css/logout_modal.css">

<!-- ── Logout Confirmation Modal ──────────────────────────────────────── -->
<div id="logout_overlay" style="display:none;">
	<div id="logout_modal">

		<!-- Icon -->
		<div id="logout_icon">
			<svg width="28" height="28" viewBox="0 0 24 24" fill="none"
				stroke="currentColor" stroke-width="2"
				stroke-linecap="round" stroke-linejoin="round">
				<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
				<polyline points="16 17 21 12 16 7"/>
				<line x1="21" y1="12" x2="9" y2="12"/>
			</svg>
		</div>

		<h2 id="logout_title">Sign Out?</h2>
		<p id="logout_body">
			Your session will be ended, are you sure you would like to log out?
		</p>

		<div id="logout_actions">
			<button id="logout_cancel" type="button">Cancel</button>
			<button id="logout_confirm" type="button">Yes, I would like to sign out</button>
		</div>

		<p id="logout_error" style="display:none;"></p>

	</div>
</div>

<!-- ── Inline AJAX Script ──────────────────────────────────────────────── -->
<script>
	(function () {

		var overlay     = document.getElementById('logout_overlay');
		var cancel_btn  = document.getElementById('logout_cancel');
		var confirm_btn = document.getElementById('logout_confirm');
		var error_msg   = document.getElementById('logout_error');

		function open_modal() {
			overlay.style.display = 'flex';
			cancel_btn.focus();
			document.addEventListener('keydown', handle_key_down);
		}

		function close_modal() {
			overlay.style.display = 'none';
			error_msg.style.display = 'none';
			error_msg.textContent = '';
			confirm_btn.disabled = false;
			confirm_btn.textContent = 'Yes, I would like to sign out';
			document.removeEventListener('keydown', handle_key_down);
		}

		function handle_key_down(e) {
			if (e.key === 'Escape') close_modal();
		}

		overlay.addEventListener('click', function (e) {
			if (e.target === overlay) close_modal();
		});

		cancel_btn.addEventListener('click', close_modal);

		confirm_btn.addEventListener('click', function () {
			confirm_btn.disabled = true;
			confirm_btn.textContent = 'Signing out...';
			error_msg.style.display = 'none';

			var xhr = new XMLHttpRequest();
			xhr.open('POST', 'logout.php', true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

			xhr.onload = function () {
				if (xhr.status === 200) {
					var response;
					try {
						response = JSON.parse(xhr.responseText);
					} catch (e) {
						show_error('Unexpected server response. Please try again.');
						return;
					}

					if (response.success) {
						document.cookie = 'access_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
						window.location.href = response.redirect || 'logged-out.html';
					} else {
						show_error(response.message || 'Logout failed. Please try again.');
					}
				} else {
					show_error('Server error (' + xhr.status + '). Please try again.');
				}
			};

			xhr.onerror = function () {
				show_error('Network error. Please check your connection and try again.');
			};

			xhr.send();
		});

		function show_error(msg) {
			error_msg.textContent = msg;
			error_msg.style.display = 'block';
			confirm_btn.disabled = false;
			confirm_btn.textContent = 'Yes, I would like to sign out';
		}

		document.addEventListener('click', function (e) {
			if (e.target.closest('[data-logout]')) {
				e.preventDefault();
				open_modal();
			}
		});

	})();
</script>
