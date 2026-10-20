<style>
.bounty-hero { background: linear-gradient(135deg, #830866 0%, #a10a7f 100%); color: #fff; padding: 2.5rem 0; margin-bottom: 1.5rem; }
.bounty-hero-title { font-size: 1.9rem; font-weight: 800; margin: 0 0 .5rem; color: #fff; }
.bounty-hero-subtitle { opacity: .92; margin: 0 0 1rem; max-width: 640px; }
.bounty-container { padding-bottom: 3rem; }
.btn-bounty-primary { background: linear-gradient(135deg, #830866 0%, #a10a7f 100%); color: #fff; border: none; border-radius: 12px; font-weight: 600; padding: .7rem 1.4rem; }
.btn-bounty-primary:hover { color: #fff; filter: brightness(1.06); }
.btn-outline-bounty { border: 2px solid #830866; color: #830866; background: #fff; border-radius: 12px; font-weight: 600; padding: .7rem 1.4rem; }
.btn-outline-bounty:hover { background: rgba(131,8,102,.06); color: #830866; }
.bounty-card { background: #fff; border: 1px solid rgba(0,0,0,.07); border-radius: 16px; padding: 1.25rem; box-shadow: 0 2px 10px rgba(0,0,0,.04); margin-bottom: 1.25rem; }
.bounty-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.bounty-stat { background: #fff; border: 1px solid rgba(0,0,0,.07); border-radius: 14px; padding: 1rem; text-align: center; }
.bounty-stat-num { font-size: 1.5rem; font-weight: 700; color: #830866; }
.bounty-stat-label { font-size: .78rem; color: #718096; text-transform: uppercase; letter-spacing: .5px; }
.bounty-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; }
.bounty-item { display: block; background: #fff; border: 1px solid rgba(0,0,0,.07); border-radius: 16px; padding: 1.25rem; text-decoration: none; color: inherit; transition: .2s ease; }
.bounty-item:hover { transform: translateY(-4px); box-shadow: 0 10px 24px rgba(131,8,102,.12); border-color: #830866; color: inherit; text-decoration: none; }
.bounty-item-head { display: flex; align-items: center; gap: 12px; margin-bottom: .75rem; }
.bounty-item-name, .bounty-detail-name { margin: 0 0 4px; font-weight: 700; }
.bounty-item-desc { color: #718096; font-size: .9rem; margin-bottom: .75rem; }
.bounty-avatar { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, #830866, #a10a7f); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem; flex-shrink: 0; }
.bounty-avatar.lg { width: 64px; height: 64px; font-size: 1.6rem; }
.bounty-progress { height: 10px; background: #eee; border-radius: 10px; overflow: hidden; }
.bounty-progress-bar { height: 100%; background: linear-gradient(135deg, #830866, #a10a7f); border-radius: 10px; transition: width .6s ease; }
.bounty-amounts { display: flex; gap: 6px; align-items: baseline; font-size: .95rem; color: #718096; }
.bounty-amounts strong { color: #2d3748; font-size: 1.1rem; }
.bounty-pill { display: inline-block; font-size: .72rem; font-weight: 600; padding: 3px 10px; border-radius: 20px; background: rgba(131,8,102,.1); color: #830866; }
.bounty-pill-released { background: rgba(40,167,69,.12); color: #1e7e34; }
.bounty-pill-refunded { background: rgba(108,117,125,.12); color: #555; }
.bounty-pill-claim_pending { background: rgba(255,159,0,.15); color: #b06f00; }
.bounty-empty { grid-column: 1 / -1; text-align: center; padding: 3rem; color: #718096; }
.bounty-empty i { font-size: 2.5rem; color: #830866; opacity: .5; margin-bottom: .5rem; }
.bounty-back { display: inline-block; margin-bottom: 1rem; color: #830866; text-decoration: none; font-weight: 600; }
.bounty-detail-head { display: flex; align-items: center; gap: 1rem; }
.bounty-contrib { display: flex; justify-content: space-between; padding: .6rem 0; border-bottom: 1px solid rgba(0,0,0,.06); }
.bounty-contrib:last-child { border-bottom: none; }
.bounty-mod { border: 1px solid rgba(131,8,102,.3); background: rgba(131,8,102,.03); }
</style>
