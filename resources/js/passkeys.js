/**
 * Passkey (WebAuthn) client, loaded only by the passkey Blade components
 * (components/auth/passkey-registration and passkey-verify) via @vite.
 *
 * Exposes the @laravel/passkeys client as `window.Passkeys` and fires
 * `passkeys:ready` on window so those Alpine components can detect support
 * once this module has loaded.
 */
import { Passkeys } from '@laravel/passkeys';

window.Passkeys = Passkeys;
window.dispatchEvent(new CustomEvent('passkeys:ready'));
