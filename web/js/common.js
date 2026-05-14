
function escapeHtml(text) { const d=document.createElement('div');d.textContent=text;return d.innerHTML; }
function postWithCsrf(url,body,isJson=true){const t=document.querySelector('meta[name="csrf-token"]')?.content;const h={};if(isJson)h['Content-Type']='application/json';if(t)h['X-CSRF-Token']=t;return fetch(url,{method:'POST',headers:h,body:isJson?JSON.stringify(body):body});}
function showNotification(msg,type='info'){const n=document.createElement('div');n.className=`notification notification-${type}`;n.textContent=msg;n.style.cssText=`position:fixed;top:20px;right:20px;background:${type==='success'?'#10b981':type==='error'?'#ef4444':'#3b82f6'};color:white;padding:12px 20px;border-radius:8px;z-index:10000;transform:translateX(100%);transition:transform 0.3s`;document.body.appendChild(n);setTimeout(()=>n.style.transform='translateX(0)',10);setTimeout(()=>{n.style.transform='translateX(100%)';setTimeout(()=>n.remove(),300);},3000);}

let deleteModalCallback=null;
function showDeleteModal(text,onConfirm){const m=document.getElementById('delete-modal');const t=document.getElementById('delete-modal-text');const b=document.getElementById('delete-modal-confirm');if(t)t.textContent=text||'Удалить?';deleteModalCallback=onConfirm;if(b&&!b.hasAttribute('data-handler-attached')){b.onclick=()=>{hideDeleteModal();if(deleteModalCallback)deleteModalCallback();};b.setAttribute('data-handler-attached','true');}if(m){m.classList.remove('hidden');m.classList.add('show');}}
function hideDeleteModal(){const m=document.getElementById('delete-modal');if(m){m.classList.add('hidden');m.classList.remove('show');}}

document.addEventListener('click',(e)=>{
    const deleteModal=document.getElementById('delete-modal');
    if(deleteModal&&deleteModal.classList.contains('show')&&e.target===deleteModal){
        hideDeleteModal();
    }
    const postModal=document.getElementById('post-modal');
    if(postModal&&postModal.classList.contains('show')&&e.target.classList.contains('modal-overlay')){
        closePostModal();
    }
    const profilePostModal=document.getElementById('profile-post-modal');
    if(profilePostModal&&profilePostModal.classList.contains('show')&&e.target.classList.contains('modal-overlay')){
        closeProfilePostModal();
    }
    const storyViewModal=document.getElementById('story-view-modal');
    if(storyViewModal&&storyViewModal.classList.contains('show')&&e.target===storyViewModal){
        hideStoryView();
    }
});

async function handleLike(postId){if(!window.currentUserId){showNotification('Войдите, чтобы поставить лайк','error');return;}try{const r=await postWithCsrf('/api/post/like',{post_id:postId});const res=await r.json();if(res.success){const p=document.querySelector(`[data-post-id="${postId}"]`);if(p){const b=p.querySelector('.btn-like');const c=p.querySelector('.likes-count');if(b)b.classList.toggle('liked',res.liked||res.is_liked);if(c)c.textContent=`${res.likes_count} лайков`;showNotification(res.liked||res.is_liked?'Лайк поставлен':'Лайк убран','success');}}}catch(e){}}
async function handleSave(postId){if(!window.currentUserId){showNotification('Войдите, чтобы сохранить','error');return;}try{const r=await postWithCsrf('/api/post/save',{post_id:postId});const res=await r.json();if(res.success){const p=document.querySelector(`[data-post-id="${postId}"]`);if(p){const b=p.querySelector('.btn-save');const c=p.querySelector('.saves-count');if(b)b.classList.toggle('saved');if(c)c.textContent=`${res.saves_count} сохранений`;showNotification(res.saved?'Сохранено':'Удалено из сохраненных','success');}}}catch(e){}}
async function toggleRepost(postId){if(!window.currentUserId){showNotification('Войдите, чтобы сделать репост','error');return;}try{const r=await postWithCsrf('/api/repost',{post_id:postId});const res=await r.json();if(res.success){const p=document.querySelector(`[data-post-id="${postId}"]`);if(p){const b=p.querySelector('.btn-repost');const c=p.querySelector('.reposts-count');if(b)b.classList.toggle('reposted');if(c)c.textContent=`${res.reposts_count} репостов`;showNotification(res.reposted?'Репост сделан':'Репост отменен','success');}}}catch(e){}}
async function deletePost(postId){showDeleteModal('Удалить пост?',async()=>{try{const r=await fetch(`/post/delete?id=${postId}`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf-token"]')?.content}});const res=await r.json();if(res.success){showNotification('Пост удалён','success');const p=document.querySelector(`[data-post-id="${postId}"]`);if(p)p.remove();}else{showNotification(res.error||'Ошибка','error');}}catch(e){}});}
async function deleteComment(commentId, postId) {
    showDeleteModal('Удалить комментарий?', async () => {
        try {
            const r = await postWithCsrf('/api/comment/delete', { comment_id: commentId });
            const res = await r.json();
            
            if (res.success) {
                showNotification('Комментарий удалён', 'success');
                
                const c = document.querySelector(`[data-comment-id="${commentId}"]`);
                if (c) {
                    c.remove();

                    const postEl = document.querySelector(`[data-post-id="${postId}"]`);

                    const modalCommentsList = document.getElementById('modal-comments-list');
                    if (modalCommentsList && modalCommentsList.querySelectorAll('.comment').length === 0) {

                        if (typeof loadModalComments === 'function') {
                            await loadModalComments(postId);
                        } else {

                            modalCommentsList.innerHTML = '<div class="empty-comments"><p>Нет комментариев.</p></div>';
                        }
                    }

                    if (postEl) {
                        const list = postEl.querySelector('.comments-list');
                        if (list && list.querySelectorAll('.comment').length === 0) {

                            list.innerHTML = `
                                <div class="empty-comments">
                                    <p>Нет комментариев.</p>
                                </div>
                            `;
                        }
                    }
                }

                const postEl = document.querySelector(`[data-post-id="${postId}"]`);
                if (postEl) {
                    const countEl = postEl.querySelector('.comments-count');
                    if (countEl) {
                        const currentCount = parseInt(countEl.textContent) || 0;
                        if (currentCount > 0) {
                            countEl.textContent = `${currentCount - 1} комментариев`;
                        } else {
                            countEl.textContent = '0 комментариев';
                        }
                    }
                }
            } else {
                showNotification(res.error || 'Ошибка', 'error');
            }
        } catch (e) {
            
        }
    });
}

window.handleLike=handleLike;
window.handleSave=handleSave;
window.toggleRepost=toggleRepost;
window.deletePost=deletePost;
window.deleteComment=deleteComment;
window.showDeleteModal=showDeleteModal;
window.hideDeleteModal=hideDeleteModal;
window.showNotification=showNotification;
window.postWithCsrf=postWithCsrf;
window.escapeHtml=escapeHtml;
