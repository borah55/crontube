// Simplified Video Player Application
class SimpleVideoPlayer {
    constructor() {
        this.videos = [
            {
                id: 1,
                title: "Archita Phukan Viral Video",
                src: "videos/archita-phukan-viral-video.mp4",
                duration: "1:34",
                views: "1.2M",
                uploadDate: "2 days ago"
            },
            {
                id: 2,
                title: "Latest Video Content",
                src: "videos/VID_20250709154938.mp4",
                duration: "0:10",
                views: "856K",
                uploadDate: "1 day ago"
            }
        ];
        
        this.currentVideoIndex = 0;
        this.isPlaying = false;
        
        this.initializeElements();
        this.setupEventListeners();
        this.loadVideoList();
        this.loadVideo(0);
        this.generateThumbnails();
    }
    
    initializeElements() {
        // Video player elements
        this.videoPlayer = document.getElementById('mainPlayer');
        this.playButton = document.getElementById('playButton');
        this.videoTitle = document.getElementById('videoTitle');
        this.videoViews = document.getElementById('videoViews');
        this.uploadDate = document.getElementById('uploadDate');
        this.videoList = document.getElementById('videoList');
        this.loadingSpinner = document.getElementById('loadingSpinner');
    }
    
    setupEventListeners() {
        // Video player events
        this.videoPlayer.addEventListener('loadstart', () => this.showLoading());
        this.videoPlayer.addEventListener('canplay', () => this.hideLoading());
        this.videoPlayer.addEventListener('ended', () => this.onVideoEnded());
        this.videoPlayer.addEventListener('play', () => this.onVideoPlay());
        this.videoPlayer.addEventListener('pause', () => this.onVideoPause());
        this.videoPlayer.addEventListener('error', (e) => this.onVideoError(e));
        
        // Play button overlay
        this.playButton.addEventListener('click', () => this.togglePlayPause());
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
        
        // Prevent context menu on video
        this.videoPlayer.addEventListener('contextmenu', (e) => e.preventDefault());
    }
    
    loadVideo(index) {
        if (index < 0 || index >= this.videos.length) return;
        
        this.currentVideoIndex = index;
        const video = this.videos[index];
        
        this.showLoading();
        
        // Update video source
        this.videoPlayer.src = video.src;
        this.videoPlayer.load();
        
        // Update video information
        this.videoTitle.textContent = video.title;
        this.videoViews.textContent = video.views + ' views';
        this.uploadDate.textContent = video.uploadDate;
        
        // Update active video in list
        this.updateActiveVideoInList();
        
        // Update page title
        document.title = video.title;
        
        // Reset player state
        this.isPlaying = false;
        this.updatePlayButton();
    }
    
    loadVideoList() {
        this.videoList.innerHTML = '';
        
        this.videos.forEach((video, index) => {
            const videoItem = this.createVideoListItem(video, index);
            this.videoList.appendChild(videoItem);
        });
    }
    
    createVideoListItem(video, index) {
        const item = document.createElement('div');
        item.className = 'video-item';
        item.dataset.index = index;
        item.tabIndex = 0; // Make it focusable for accessibility
        
        item.innerHTML = `
            <div class="video-thumbnail">
                <div class="thumbnail-placeholder" style="background: linear-gradient(45deg, #333, #555); display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px;">
                    <i class="fas fa-play"></i>
                </div>
                <span class="video-duration">${video.duration}</span>
            </div>
            <div class="video-details">
                <h3 class="video-item-title">${video.title}</h3>
                <div class="video-item-meta">
                    <span>${video.views} views</span> • <span>${video.uploadDate}</span>
                </div>
            </div>
        `;
        
        // Click event
        item.addEventListener('click', () => {
            this.loadVideo(index);
        });
        
        // Keyboard event for accessibility
        item.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.loadVideo(index);
            }
        });
        
        return item;
    }
    
    updateActiveVideoInList() {
        const videoItems = this.videoList.querySelectorAll('.video-item');
        videoItems.forEach((item, index) => {
            item.classList.toggle('active', index === this.currentVideoIndex);
        });
    }
    
    generateThumbnails() {
        // Generate thumbnails for videos using canvas
        this.videos.forEach((video, index) => {
            const tempVideo = document.createElement('video');
            tempVideo.src = video.src;
            tempVideo.crossOrigin = 'anonymous';
            tempVideo.muted = true;
            
            tempVideo.addEventListener('loadeddata', () => {
                tempVideo.currentTime = 1; // Seek to 1 second for thumbnail
            });
            
            tempVideo.addEventListener('seeked', () => {
                try {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    
                    canvas.width = 120;
                    canvas.height = 68;
                    
                    ctx.drawImage(tempVideo, 0, 0, canvas.width, canvas.height);
                    
                    const thumbnailUrl = canvas.toDataURL();
                    this.updateThumbnail(index, thumbnailUrl);
                } catch (e) {
                    console.log('Could not generate thumbnail for video', index);
                }
            });
            
            tempVideo.addEventListener('error', () => {
                console.log('Error loading video for thumbnail generation:', video.src);
            });
        });
    }
    
    updateThumbnail(index, thumbnailUrl) {
        const videoItem = this.videoList.children[index];
        if (videoItem) {
            const placeholder = videoItem.querySelector('.thumbnail-placeholder');
            if (placeholder) {
                placeholder.style.backgroundImage = `url(${thumbnailUrl})`;
                placeholder.style.backgroundSize = 'cover';
                placeholder.style.backgroundPosition = 'center';
                placeholder.innerHTML = '';
            }
        }
    }
    
    togglePlayPause() {
        if (this.videoPlayer.paused) {
            this.videoPlayer.play().catch(e => console.error('Play failed:', e));
        } else {
            this.videoPlayer.pause();
        }
    }
    
    onVideoPlay() {
        this.isPlaying = true;
        this.updatePlayButton();
    }
    
    onVideoPause() {
        this.isPlaying = false;
        this.updatePlayButton();
    }
    
    onVideoEnded() {
        this.isPlaying = false;
        this.updatePlayButton();
        
        // Auto-play next video if available
        if (this.currentVideoIndex < this.videos.length - 1) {
            setTimeout(() => {
                this.loadVideo(this.currentVideoIndex + 1);
                this.videoPlayer.play().catch(e => console.error('Autoplay failed:', e));
            }, 1000);
        }
    }
    
    onVideoError(e) {
        console.error('Video error:', e);
        this.hideLoading();
        this.showErrorMessage('Failed to load video. Please try again.');
    }
    
    updatePlayButton() {
        const icon = this.playButton.querySelector('i');
        if (this.isPlaying) {
            icon.className = 'fas fa-pause';
        } else {
            icon.className = 'fas fa-play';
        }
    }
    
    handleKeyboardShortcuts(e) {
        // Prevent shortcuts when typing in input fields
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        switch (e.key.toLowerCase()) {
            case ' ':
            case 'k':
                e.preventDefault();
                this.togglePlayPause();
                break;
            case 'arrowleft':
                e.preventDefault();
                this.videoPlayer.currentTime = Math.max(0, this.videoPlayer.currentTime - 10);
                break;
            case 'arrowright':
                e.preventDefault();
                this.videoPlayer.currentTime = Math.min(this.videoPlayer.duration, this.videoPlayer.currentTime + 10);
                break;
            case 'arrowup':
                e.preventDefault();
                this.videoPlayer.volume = Math.min(1, this.videoPlayer.volume + 0.1);
                break;
            case 'arrowdown':
                e.preventDefault();
                this.videoPlayer.volume = Math.max(0, this.videoPlayer.volume - 0.1);
                break;
            case 'm':
                e.preventDefault();
                this.videoPlayer.muted = !this.videoPlayer.muted;
                break;
            case 'f':
                e.preventDefault();
                this.toggleFullscreen();
                break;
            case 'n':
                e.preventDefault();
                if (this.currentVideoIndex < this.videos.length - 1) {
                    this.loadVideo(this.currentVideoIndex + 1);
                }
                break;
            case 'p':
                e.preventDefault();
                if (this.currentVideoIndex > 0) {
                    this.loadVideo(this.currentVideoIndex - 1);
                }
                break;
        }
    }
    
    toggleFullscreen() {
        if (!document.fullscreenElement) {
            this.videoPlayer.requestFullscreen().catch(e => console.error('Fullscreen failed:', e));
        } else {
            document.exitFullscreen();
        }
    }
    
    showLoading() {
        this.loadingSpinner.classList.add('active');
    }
    
    hideLoading() {
        this.loadingSpinner.classList.remove('active');
    }
    
    showErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-secondary);
            border: 1px solid #ff4444;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #ff4444;
            z-index: 4000;
            max-width: 300px;
        `;
        errorDiv.innerHTML = `
            <p>${message}</p>
            <button onclick="this.parentElement.remove()" style="
                background: #ff4444;
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                margin-top: 10px;
                cursor: pointer;
            ">Close</button>
        `;
        
        document.body.appendChild(errorDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize video player
    const videoPlayer = new SimpleVideoPlayer();
    
    console.log('Simple Video Player initialized successfully');
});

