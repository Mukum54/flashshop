/**
 * ecosystem.config.js — PM2 Configuration
 * FlashShop E-Commerce — AWS EC2 Production
 *
 * Usage:
 *   pm2 start ecosystem.config.js
 *   pm2 reload ecosystem.config.js   (zero-downtime reload)
 *   pm2 logs flashshop
 */
module.exports = {
    apps: [
        {
            name: 'flashshop',
            script: 'php',
            args: '-S 0.0.0.0:8000 -t .',
            interpreter: 'none',

            // Process management
            autorestart: true,
            watch: false,
            max_memory_restart: '512M',

            // Environment — production
            env_production: {
                APP_ENV: 'production',
            },

            // Logging
            log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
            out_file: './logs/out.log',
            error_file: './logs/error.log',
            merge_logs: true,

            // Restart strategy
            restart_delay: 3000,
            max_restarts: 10,
            min_uptime: '5s',
        },
    ],
};
