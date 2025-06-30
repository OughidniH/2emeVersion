{if $subcategories}
  <div class="subcategory-slider-wrapper">
    <div class="subcategory-slider">
      {foreach from=$subcategories item=subcat}
        <div class="subcategory-item">
          <a href="{$link->getCategoryLink($subcat.id_category)}">
            <img class="subcategory-img" src="{$subcat.image_url}" alt="{$subcat.name|escape:'html'}">
            <div class="subcategory-name">{$subcat.name}</div>
            <div class="subcategory-count">{$subcat.product_count} products</div>
          </a>
        </div>
      {/foreach}

    </div>
    <button class="scroll-left">‹</button>
    <button class="scroll-right">›</button>
  </div>

{/if}




<script>
  document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.subcategory-slider');
    document.querySelector('.scroll-left').addEventListener('click', () => {
      container.scrollBy({ left: -200, behavior: 'smooth' });
    });
    document.querySelector('.scroll-right').addEventListener('click', () => {
      container.scrollBy({ left: 200, behavior: 'smooth' });
    });
  });
</script>