/**
 * Category / Anime Details Logic
 *
 * This script handles frontend logic for fetching and displaying anime details
 * and recent releases via API.
 *
 * NOTE: The original API endpoint (herokuapp) appears to be legacy/defunct.
 * This file is retained for reference but core functionality has been moved
 * to server-side PHP processing in `anime-details.php`.
 */

const url = window.location.pathname.replace("/", "");
// const url = "naruto"
const apiURl = `https://api-indianime.herokuapp.com`;

function loadAnimeDetails() {
    apiUrlAnimeDetails = `${apiURl}/getAnime/${url}`
    async function loadAnime() {
        try {
            const response = await fetch(apiUrlAnimeDetails);
            const anime = await response.json();
            //console.log(anime)
            function loadDetail() {
                let name = anime['name'];
                const animeName = document.getElementById('animeName');
                if(animeName) animeName.innerHTML = name;

                const animeImg = document.getElementById('animeImg');
                if(animeImg) animeImg.setAttribute('src', `${anime['img_url']}`);

                const animeInfo = document.getElementById('animeInfo');
                if(animeInfo) animeInfo.innerHTML = `<span>Plot Summary: </span>${anime['about'].replace('Plot Summary:', "")}`

                const animeReleased = document.getElementById('animeReleased');
                if(animeReleased) animeReleased.innerHTML = `<span>Released: </span>${anime['released'].replace('Released:', "")}`

                const animeOtherName = document.getElementById('animeOtherName');
                if(animeOtherName) animeOtherName.innerHTML = `<span>Other name: </span>${anime['othername'].replace("Other name", "")}`

                function animeStatus() {
                    let statusUrl;
                    const status = anime['status'].replace('Status:', "");
                    // console.log(status)
                    if (anime['status'] == `Status: \n                                      Completed\n                                  `) {
                        statusUrl = '/completed-anime.html'
                        title = 'Completed'
                    } else {
                        statusUrl = '/ongoing-anime.html'
                        title = 'Ongoing'
                    }
                    const animeStatus = document.getElementById('animeStatus');
                    if(animeStatus) {
                        animeStatus.innerHTML = `<span>Status: </span><a href="${statusUrl}" title="${title} Anime">${status}</a>`
                    }
                }
                animeStatus()

                const genre = document.getElementById('genre');
                if(genre) genre.innerHTML = `<span>Genre: </span>${anime['genre'].replace('Genre: ', '')}`;

                const h2title = document.getElementById('h2title');
                if(h2title) h2title.innerHTML = `${anime['name']}`
            }
            loadDetail()
            function loadEpisode() {
                const episode_related = document.getElementById('episode_related');
                if(episode_related) {
                    let episode = anime['episode_id'];
                    let episodeHTML = ""
                    let episodeContent;
                    episode.forEach(function (element, index) {
                        episodeContent = `
                            <li>
                              <a href="${element}">
                                <div class="name"><span>EP</span> ${index + 1}</div>
                                <div class="vien"></div>
                                <div class="cate">SUB</div>
                              </a>
                            </li>`
                        episodeHTML += episodeContent
                    });
                    episode_related.innerHTML = episodeHTML
                }
            }
            loadEpisode();
        } catch (error) {
            console.warn("Legacy API call failed or element missing:", error);
        }
    }
    loadAnime()
}
// loadAnimeDetails() // Disabled as PHP now handles this

function loadRecentRelease() {
    async function loadRecent() {
        try {
            const apiUrlRecentReleases = `${apiURl}/getRecent/1`;
            const response = await fetch(apiUrlRecentReleases);
            const recentReleases = await response.json();
            //console.log(recentReleases);
            const recentEpisodesContainer = document.getElementById('recentEpisodes');
            if (!recentEpisodesContainer) return;

            let recentEpisodesHTML = "";
            let recentEpisodesContent;

            recentReleases.forEach(function (element) {
                recentEpisodesContent = `
                <li>
                 <a href="${element['r_anime_id']}"
                  title="${element['r_name']}">
                  <div class="thumbnail-recent"
                    style="background: url('${element['r_img_url']}');">
                  </div>
                  ${element['r_name']}
                 </a>
                 <a href="${element['r_anime_id']}"
                  title="${element['r_name']}">
                  <p class="time_2">${element['episode_num']}</p>
                 </a>
                </li>
                `;
                recentEpisodesHTML += recentEpisodesContent;
            });
            recentEpisodesContainer.innerHTML = recentEpisodesHTML;
        } catch (e) {
            // console.warn("API Error", e);
        }
    }
    loadRecent();
}
// loadRecentRelease(); // Disabled

function loadDisqus(){
    const disqusContainer = document.getElementById('loadDisqus');
    if (disqusContainer) {
        disqusContainer.innerHTML = `
        <script>
            var disqus_config = function () {
                this.page.url = window.location.href;
            };
            (function () {  // DON'T EDIT BELOW THIS LINE
                var d = document, s = d.createElement('script');
                s.src = '//gogoanimetv.disqus.com/embed.js';
                s.setAttribute('data-timestamp', +new Date());
                (d.head || d.body).appendChild(s);
            })();
        </script>
        <noscript>Please enable JavaScript to view the <a
                href="https://disqus.com/?ref_noscript" rel="nofollow">comments powered by
                Disqus.</a></noscript>`
    }
}
// loadDisqus();
