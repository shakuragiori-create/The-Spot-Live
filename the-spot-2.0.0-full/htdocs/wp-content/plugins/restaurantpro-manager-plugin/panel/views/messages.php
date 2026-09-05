<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$me = get_current_user_id();
$table = $wpdb->prefix . 'rp_internal_messages';
$staff_roles = [ 'administrator', 'rp_restaurant_admin', 'rp_manager', 'rp_cashier', 'rp_kitchen_staff', 'rp_waiter' ];
$staff_users = get_users([ 'role__in' => $staff_roles, 'orderby' => 'display_name', 'order' => 'ASC', 'number' => 200 ]);
$names = [];
$avatars = [];
foreach ( $staff_users as $u ) {
    $names[$u->ID] = $u->display_name ?: $u->user_login;
    $avatars[$u->ID] = get_avatar_url( $u->ID, [ 'size' => 96 ] );
}
$all = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$table} WHERE sender_id=%d OR recipient_id=%d ORDER BY created_at ASC, id ASC LIMIT 500",
    $me, $me
) );
$contacts = [];
foreach ( $all as $m ) {
    $other = (int) $m->sender_id === $me ? (int) $m->recipient_id : (int) $m->sender_id;
    if ( $other === $me || ! isset($names[$other]) ) continue;
    if ( ! isset($contacts[$other]) ) $contacts[$other] = [ 'id'=>$other, 'name'=>$names[$other], 'avatar'=>$avatars[$other], 'messages'=>[], 'unread'=>0, 'last'=>$m->created_at ];
    $contacts[$other]['messages'][] = $m;
    $contacts[$other]['last'] = $m->created_at;
    if ( (int)$m->recipient_id === $me && !(int)$m->is_read ) $contacts[$other]['unread']++;
}
foreach ( $staff_users as $u ) {
    if ( $u->ID === $me ) continue;
    if ( ! isset($contacts[$u->ID]) ) $contacts[$u->ID] = [ 'id'=>$u->ID, 'name'=>$names[$u->ID], 'avatar'=>$avatars[$u->ID], 'messages'=>[], 'unread'=>0, 'last'=>'' ];
}
usort($contacts, function($a,$b){ if($a['last'] && !$b['last']) return -1; if(!$a['last'] && $b['last']) return 1; if($a['last'] && $b['last']) return strcmp($b['last'],$a['last']); return strcasecmp($a['name'],$b['name']); });
$selected_id = absint( $_GET['with'] ?? 0 );
if ( ! $selected_id && ! empty($contacts) ) $selected_id = (int)$contacts[0]['id'];
$selected = null;
foreach($contacts as $c){ if((int)$c['id']===$selected_id){$selected=$c;break;} }
if ( $selected && $selected['unread'] ) {
    $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET is_read=1 WHERE recipient_id=%d AND sender_id=%d AND is_read=0", $me, $selected_id ) );
    $selected['unread']=0;
}
$unread_total = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE recipient_id=%d AND is_read=0",$me));
?>
<div class="page-title-row messages-title-row">
  <div>
    <h1 class="page-title" data-i18n="Staff Chat">Staff Chat</h1>
    <p class="page-subtitle" data-i18n="Chat privately with your restaurant team.">Chat privately with your restaurant team.</p>
  </div>
  <span class="message-unread-count" data-i18n="unread"><?php echo esc_html($unread_total); ?> unread</span>
</div>

<div class="social-chat <?php echo $selected ? 'has-selected' : ''; ?>" id="spotChat">
  <aside class="chat-sidebar">
    <div class="chat-sidebar-head">
      <div><strong data-i18n="Messages">Messages</strong><small><?php echo count($staff_users)-1; ?> <span data-i18n="team members">team members</span></small></div>
      <button type="button" class="chat-new-btn" id="chatNewBtn" title="New chat" aria-label="New chat">+</button>
    </div>
    <label class="chat-search"><span aria-hidden="true">&#8981;</span><input id="chatSearch" type="search" placeholder="Search people..." data-i18n-placeholder="Search people..." autocomplete="off"></label>
    <div class="chat-people" id="chatPeople">
      <?php foreach($contacts as $c): $is_active=(int)$c['id']===$selected_id; $initial=mb_strtoupper(mb_substr($c['name'],0,1)); $last_msg=end($c['messages']); $preview=$last_msg ? wp_trim_words(wp_strip_all_tags($last_msg->message),8,'…') : 'Start a conversation'; reset($c['messages']); ?>
        <a class="chat-person <?php echo $is_active?'active':''; ?>" href="<?php echo esc_url(home_url('/panel/messages?with='.(int)$c['id'])); ?>" data-uid="<?php echo (int)$c['id']; ?>" data-name="<?php echo esc_attr(strtolower($c['name'])); ?>">
          <span class="chat-avatar"><?php if(!empty($c['avatar'])): ?><img src="<?php echo esc_url($c['avatar']); ?>" alt=""><?php else: echo esc_html($initial); endif; ?><i></i></span>
          <span class="chat-person-copy"><strong><?php echo esc_html($c['name']); ?></strong><small><?php echo esc_html($preview); ?></small></span>
          <?php if($c['unread']): ?><b class="chat-unread"><?php echo (int)$c['unread']; ?></b><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>

  <section class="chat-window <?php echo $selected?'has-contact':''; ?>" id="chatWindow">
    <?php if($selected): ?>
      <header class="chat-window-head">
        <button type="button" class="chat-back" id="chatBack" aria-label="Back">&#8249;</button>
        <span class="chat-avatar chat-avatar-large"><?php if(!empty($selected['avatar'])): ?><img src="<?php echo esc_url($selected['avatar']); ?>" alt=""><?php else: echo esc_html(mb_strtoupper(mb_substr($selected['name'],0,1))); endif; ?><i></i></span>
        <div class="chat-contact-meta"><strong><?php echo esc_html($selected['name']); ?></strong><small data-i18n="Restaurant team member">Restaurant team member</small></div>
      </header>
      <div class="chat-thread" id="chatThread">
        <div class="chat-date-divider"><span data-i18n="Conversation">Conversation</span></div>
        <?php if($selected['messages']): foreach($selected['messages'] as $m): $mine=(int)$m->sender_id===$me; ?>
          <div class="chat-bubble-row <?php echo $mine?'mine':''; ?>">
            <?php if(!$mine): ?><span class="chat-mini-avatar"><?php if(!empty($selected['avatar'])): ?><img src="<?php echo esc_url($selected['avatar']); ?>" alt=""><?php else: echo esc_html(mb_strtoupper(mb_substr($selected['name'],0,1))); endif; ?></span><?php endif; ?>
            <div class="chat-bubble-wrap">
              <?php if($m->subject && $m->subject!=='Message'): ?><strong class="chat-subject"><?php echo esc_html($m->subject); ?></strong><?php endif; ?>
              <div class="chat-bubble"><?php echo nl2br(esc_html($m->message)); ?></div>
              <time><?php echo esc_html(wp_date('d M, g:i a',strtotime($m->created_at))); ?><?php if($mine): ?> · <?php echo (int)$m->is_read ? 'Seen' : 'Sent'; endif; ?></time>
            </div>
          </div>
        <?php endforeach; else: ?>
          <div class="chat-welcome"><span class="chat-welcome-icon"><?php echo rp_panel_icon('message'); ?></span><strong data-i18n="Start a conversation">Start a conversation</strong><p data-i18n="Send a quick message to your teammate.">Send a quick message to your teammate.</p></div>
        <?php endif; ?>
      </div>
      <form method="post" class="chat-composer" id="chatComposerForm">
        <?php wp_nonce_field( 'rp_internal_message_action' ); ?><input type="hidden" name="rp_message_action" value="send"><input type="hidden" name="recipient_id" value="<?php echo (int)$selected_id; ?>"><input type="hidden" name="subject" value="Message">
        <textarea name="message" rows="1" required placeholder="Write a message..." data-i18n-placeholder="Write a message..." id="chatComposer"></textarea>
        <button type="submit" aria-label="Send" title="Send"><?php echo rp_panel_icon('message'); ?></button>
      </form>
    <?php else: ?>
      <div class="chat-welcome full"><span class="chat-welcome-icon"><?php echo rp_panel_icon('message'); ?></span><strong data-i18n="Your messages">Your messages</strong><p data-i18n="Choose a teammate to start chatting.">Choose a teammate to start chatting.</p></div>
    <?php endif; ?>
  </section>
</div>

<div class="chat-new-modal" id="chatNewModal" aria-hidden="true">
  <div class="chat-new-modal-card">
    <button type="button" class="chat-modal-close" id="chatModalClose">&times;</button>
    <h3 data-i18n="New Chat">New Chat</h3><p data-i18n="Choose someone from your team.">Choose someone from your team.</p>
    <div class="chat-new-list">
      <?php foreach($staff_users as $u): if($u->ID===$me) continue; ?><a href="<?php echo esc_url(home_url('/panel/messages?with='.(int)$u->ID)); ?>" data-uid="<?php echo (int)$u->ID; ?>"><span class="chat-avatar"><?php echo esc_html(mb_strtoupper(mb_substr($u->display_name ?: $u->user_login,0,1))); ?></span><strong><?php echo esc_html($u->display_name ?: $u->user_login); ?></strong><small><?php echo esc_html(implode(', ', $u->roles)); ?></small></a><?php endforeach; ?>
    </div>
  </div>
</div>

<script>
(function(){
  var root = document.getElementById('spotChat');
  if (!root) return;

  // Search
  var search = document.getElementById('chatSearch');
  if (search) {
    search.addEventListener('input', function() {
      var q = this.value.toLowerCase().trim();
      document.querySelectorAll('.chat-person').forEach(function(p) {
        p.style.display = !q || p.dataset.name.indexOf(q) >= 0 ? 'flex' : 'none';
      });
    });
  }

  // New chat modal
  var modal = document.getElementById('chatNewModal');
  var openBtn = document.getElementById('chatNewBtn');
  var closeBtn = document.getElementById('chatModalClose');
  if (openBtn) openBtn.onclick = function() { modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); };
  if (closeBtn) closeBtn.onclick = function() { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); };
  if (modal) modal.addEventListener('click', function(e) { if (e.target === modal) closeBtn.click(); });

  // Back button — return to contact list (mobile)
  var back = document.getElementById('chatBack');
  if (back) {
    back.onclick = function(e) {
      e.preventDefault();
      root.classList.remove('mobile-thread');
    };
  }

  // AJAX navigation for chat contacts — works on ALL screen sizes
  function runScripts(scope) {
    scope.querySelectorAll('script').forEach(function(old) {
      var n = document.createElement('script');
      n.textContent = old.textContent;
      old.replaceWith(n);
    });
  }

  function navigateChat(url) {
    var main = document.querySelector('.panel-main');
    if (!main) { location.href = url; return; }

    fetch(url, { credentials: 'same-origin', cache: 'no-store' })
      .then(function(r) {
        if (!r.ok) throw 0;
        return r.text();
      })
      .then(function(html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var next = doc.querySelector('.panel-main');
        if (!next) throw 0;
        var swap = function() {
          main.replaceWith(next);
          history.pushState({ chat: 1 }, '', url);
          runScripts(next);
          // On mobile, show the thread view after navigation
          var newRoot = document.getElementById('spotChat');
          if (newRoot && window.innerWidth < 800) {
            newRoot.classList.add('mobile-thread');
          }
        };
        if (document.startViewTransition) document.startViewTransition(swap);
        else swap();
      })
      .catch(function() {
        location.href = url;
      });
  }

  // Contact click — use AJAX navigation on all screen sizes
  document.querySelectorAll('.chat-person').forEach(function(a) {
    a.addEventListener('click', function(e) {
      e.preventDefault();
      navigateChat(a.href);
    });
  });

  // New chat modal contact click
  document.querySelectorAll('.chat-new-list a').forEach(function(a) {
    a.addEventListener('click', function(e) {
      e.preventDefault();
      if (modal) modal.classList.remove('open');
      navigateChat(a.href);
    });
  });

  // Handle browser back/forward
  window.addEventListener('popstate', function(e) {
    if (e.state && e.state.chat) {
      navigateChat(location.href);
    } else {
      location.reload();
    }
  });

  // Composer — auto-resize, Enter to send, Shift+Enter for newline
  var box = document.getElementById('chatComposer');
  if (box) {
    box.addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 130) + 'px';
    });
    box.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (this.value.trim()) {
          this.form.requestSubmit();
        }
      }
    });
    // Focus composer when thread is visible — helps on mobile
    setTimeout(function() { box.focus(); }, 300);
  }

  // Scroll chat thread to bottom
  var thread = document.getElementById('chatThread');
  if (thread) thread.scrollTop = thread.scrollHeight;

  // If URL has ?with= and we're on mobile, show thread view
  if (window.innerWidth < 800 && document.querySelector('.chat-window.has-contact')) {
    root.classList.add('mobile-thread');
  }

  // Message polling
  function messageNotify() {
    var cfg = window.RPNotifications || {};
    var ajax = cfg.ajaxUrl || '';
    var nonce = cfg.nonce || '';
    if (!ajax) return;

    var key = 'rp_message_cursor_v1';
    var cursor = parseInt(localStorage.getItem(key) || '0', 10) || 0;
    var b = new URLSearchParams();
    b.append('action', 'rp_messages_poll');
    b.append('nonce', nonce);
    b.append('after_id', cursor);

    fetch(ajax, { method: 'POST', credentials: 'same-origin', body: b })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (!res || !res.success) return;
        var ms = res.data.messages || [];
        ms.forEach(function(m) {
          cursor = Math.max(cursor, m.id || 0);
          if ('Notification' in window && Notification.permission === 'granted') {
            try { new Notification('New message from ' + m.sender, { body: m.message, tag: 'rp-msg-' + m.id }); } catch(e) {}
          }
          document.dispatchEvent(new CustomEvent('rp:message', { detail: m }));
        });
        if (ms.length) localStorage.setItem(key, cursor);
        var c = document.querySelector('.message-unread-count');
        if (c) c.textContent = (res.data.unread || 0) + ' unread';
      })
      .catch(function() {});
  }

  if (window.RPNotifications && window.RPNotifications.ajaxUrl) {
    document.addEventListener('pointerdown', function() {
      if ('Notification' in window && Notification.permission === 'default') {
        try { Notification.requestPermission(); } catch(e) {}
      }
    }, { once: true });
    messageNotify();
    setInterval(messageNotify, 3500);
  }
})();
</script>
