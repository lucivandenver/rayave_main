# RayAve Assistant (Groq)

1. **Environment variables** (never commit your actual key):
   - `GROQ_API_KEY` – your Groq secret key (example: `gsk_xxx`).
   - `GROQ_API_BASE` – defaults to `https://api.groq.com/openai/v1`.
   - `GROQ_MODEL` – defaults to `llama-3.3-70b-versatile`.

   On Windows PowerShell you can set the key for the current session with:
   ```powershell
   $env:GROQ_API_KEY = 'gsk_your_real_key_here'
   ```

2. **Endpoint**: the landing page posts chat prompts to `chatbot/rayave-assistant.php`, which gathers live product/category/coupon data from MySQL before calling Groq.

3. **Response flow**: `assets/js/rayave-chatbot.js` sends the user message, shows a typing indicator, and renders the assistant reply once Groq responds. Network/API issues fall back to a friendly error bubble.

Keep the key stored server-side only; the frontend never embeds it.