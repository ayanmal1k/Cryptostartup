@for($i = 0; $i < (isset($limit) ? $limit : 0); $i++)
    <div class="messenger-skeleton messenger-skeleton--contact" aria-hidden="true">
        <div class="messenger-skeleton__avatar"></div>
        <div class="messenger-skeleton__body">
            <div class="messenger-skeleton__line messenger-skeleton__line--title"></div>
            <div class="messenger-skeleton__line messenger-skeleton__line--message"></div>
        </div>
    </div>
@endfor
