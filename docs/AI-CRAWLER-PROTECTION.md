# AI Crawler Protection

FASTNETPAY is a private operational system for ISP billing, hotspot payments, tenant management, router provisioning, SMS, and support workflows.

## Implemented Controls

- `robots.txt` denies all crawlers.
- `.well-known/ai.txt` states that AI training, scraping, and agentic browsing are not authorized.
- PHP sends `X-Robots-Tag` headers by default.
- Production Nginx blocks common AI crawler user-agents.
- Application throttling blocks common AI crawler user-agents and logs attempts.
- The Security Throttling UI can add more user-agent block rules without code changes.

## Known AI User-Agent Blocks

Current defaults include:

- GPTBot
- ChatGPT-User
- OAI-SearchBot
- ClaudeBot
- Claude-Web
- anthropic-ai
- PerplexityBot
- Bytespider
- CCBot
- Google-Extended
- Applebot-Extended
- Amazonbot
- FacebookBot
- Meta-ExternalAgent
- Diffbot
- YouBot

## Important Limitation

These controls discourage and block compliant or identifiable crawlers. They cannot stop every hostile client because any scraper can spoof a browser user-agent.

The real production protection is layered:

1. Authentication and permissions.
2. Cloudflare/WAF.
3. Nginx rate limiting.
4. FASTNETPAY app throttling.
5. Logs, alerts, and IP blocking.
6. No public database/phpMyAdmin/Docker daemon.

## Operations

Open:

```text
System / Logs -> Security Throttling
```

Use the rules panel to add:

- `user_agent` block rules for new bots.
- `ip` or `cidr` block rules for abuse.
- `ip` or `cidr` whitelist rules for trusted office/VPN monitoring IPs.

Do not whitelist wide public ranges unless you fully trust them.
