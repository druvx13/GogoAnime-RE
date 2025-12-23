/**
 * Streaming / Episode Playback Logic
 *
 * This script was originally designed to fetch streaming details from an external API.
 * The core logic has been migrated to server-side PHP (`streaming.php`), making this file
 * largely redundant but kept for historical reference or potential progressive enhancement.
 *
 * NOTE: The 'api-indianime.herokuapp.com' endpoint is legacy and should not be relied upon.
 */

const url = window.location.pathname.replace("/", "");
// const url = "naruto-episode-1"
const apiURl = `https://api-indianime.herokuapp.com`;

// Declaring Gobally
const apiUrlEpisodeDetail = `${apiURl}/getEpisode/${url}`;

function loadEpisodeDetail() {
    async function loadDetail() {
        try {
            const response = await fetch(apiUrlEpisodeDetail);
            const episodeDetail = await response.json();
            document.title = `${episodeDetail['animenamewithep']} at GogoAnime`;
            const iframez = document.getElementById('iframez');
            if(iframez) iframez.setAttribute('src', `${episodeDetail['video']}`);

            const dowloads = document.getElementById('dowloads');
            if(dowloads) dowloads.setAttribute('href', `${episodeDetail['ep_download']}`);

            const animeCategory = document.getElementById('animeCategory');
            if(animeCategory) animeCategory.setAttribute('href', `${episodeDetail['anime_info']}`);

            const animeTitle = document.getElementById("animeTitle");
            if(animeTitle) animeTitle.innerHTML = `${episodeDetail['animenamewithep']}`

            const animeTitle2 = document.getElementById('animeTitle2');
            if(animeTitle2) animeTitle2.innerHTML = `<h1>${episodeDetail['animenamewithep']} at GogoAnime</h1>`;


            //Example - ${episodeDetail['anime_info']} - category/naruto
            let animeCategoryName = `${episodeDetail['anime_info']}`;
            function animeDetails() {
                const apiUrlAnimeDetails = `${apiURl}/getAnime/${animeCategoryName.replace("/category/", "")}`
                async function loadAnimeDetails() {
                    const response = await fetch(apiUrlAnimeDetails);
                    const animeDetail = await response.json();

                    if(animeCategory) {
                        animeCategory.innerHTML = `${animeDetail['name']}`;
                        animeCategory.setAttribute('title', `${animeDetail['name']}`);
                    }
                    let aboutAnime = `${animeDetail['about']}`;
                    let aboutAnimeFinal = aboutAnime.replace("Plot Summary: ", "")

                    function metaHeadTag() {
                        // Meta tag manipulation logic
                    }
                    metaHeadTag();

                    // Episode List
                    function loadEpisode() {
                        let episodes = animeDetail['episode_id'];
                        let episode_related = document.getElementById('episode_related');
                        if(!episode_related) return;

                        let episodeHTML = "";
                        let episodeContent;
                        episodes.forEach(function (element, index) {
                            if (element == url) {
                                activeClass = "active"
                            } else {
                                activeClass = ""
                            }
                            episodeContent = `
                        <li>
                          <a href="${element}" class="${activeClass}">
                            <div class="name"><span>EP</span> ${index + 1}</div>
                            <div class="vien"></div>
                            <div class="cate">SUB</div>
                          </a>
                        </li>
                        `
                            episodeHTML += episodeContent;

                        })
                        episode_related.innerHTML = episodeHTML;

                        function previousEpisode() {
                            let anime_video_body_episodes_l = document.getElementById('anime_video_body_episodes_l');
                            if(!anime_video_body_episodes_l) return;

                            const epNumber = episodeDetail['ep_num'];
                            prevEpisode = epNumber - 1
                            arrayLink = prevEpisode - 1
                            
                            let previosHTML = "";
                            let previosHTMLContent;
                            if (prevEpisode > 0) {
                                previosHTMLContent = `
                           <a href="${episodes[arrayLink]}">
                              &lt;&lt; ${animeDetail['name']} Episode ${parseInt(episodeDetail['ep_num']) - 1}
                           </a>
                           `

                            } else {
                                previosHTMLContent = "";
                            }
                            previosHTML += previosHTMLContent;
                            anime_video_body_episodes_l.innerHTML = previosHTML;
                        }
                        previousEpisode();

                        function nextEpisode(){
                            let anime_video_body_episodes_r = document.getElementById('anime_video_body_episodes_r');
                            if(!anime_video_body_episodes_r) return;

                            let lastEpisode = animeDetail['episode_id'].length
                            let nextEpisodeHTML = ""
                            let nextEpisodeContent;
                            let currentEpisode = parseInt(episodeDetail['ep_num']);

                            if (currentEpisode < lastEpisode){
                                nextEpisodeContent = `
                                 <a href="${episodes[parseInt(episodeDetail['ep_num'])]}">
                                   ${animeDetail['name']} Episode ${parseInt(episodeDetail['ep_num']) + 1} >>
                                 </a>
                                `
                            } else {
                                nextEpisodeContent = ""
                            }
                            nextEpisodeHTML += nextEpisodeContent;
                            anime_video_body_episodes_r.innerHTML = nextEpisodeHTML;
                        }
                        nextEpisode()

                    }
                    loadEpisode();
                }
                loadAnimeDetails();
            }
            animeDetails();

            return episodeDetail;
        } catch (e) {
            console.warn("Legacy streaming API error", e);
        }
    };
    loadDetail();
};

// loadEpisodeDetail(); // Disabled

function loadRecentRelease() {
    async function loadRecent() {
        try {
            const apiUrlRecentReleases = `${apiURl}/getRecent/1`;
            const response = await fetch(apiUrlRecentReleases);
            const recentReleases = await response.json();

            const recentEpisodesContainer = document.getElementById('recentEpisodes');
            if(!recentEpisodesContainer) return;

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
        } catch (e) {}
    }
    loadRecent();
}
// loadRecentRelease(); // Disabled
