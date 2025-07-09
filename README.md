# VideoTube - Modern Video Player Website

## Overview
A modern, responsive video player website with a sleek black theme and tube-style UI design. Built with HTML, CSS, JavaScript, and PHP backend for a complete video streaming experience.

## Features

### 🎨 Design & UI
- **Modern Black Theme**: Sleek dark interface with red accent colors
- **Tube-Style Layout**: YouTube-inspired design with modern touches
- **Responsive Design**: Optimized for PC, mobile, and tablet devices
- **Smooth Animations**: Hover effects, transitions, and micro-interactions
- **Professional Typography**: Inter font family for clean readability

### 📱 Responsive Layout
- **Desktop**: Full sidebar layout with video list
- **Tablet**: Stacked layout with grid video list
- **Mobile**: Single column layout with touch-optimized controls

### 🎥 Video Player
- **HTML5 Video Player**: Native browser video controls
- **Video Streaming**: PHP-powered video serving with range request support
- **Autoplay**: Optional autoplay for next videos
- **Keyboard Shortcuts**: 
  - Space/K: Play/Pause
  - Arrow Keys: Seek and volume control
  - M: Mute/Unmute
  - F: Fullscreen
  - N/P: Next/Previous video

### 📺 Video Management
- **Video List**: Sidebar with video thumbnails and metadata
- **Video Switching**: Click to switch between videos
- **Video Information**: Title, views, upload date, description
- **Channel Information**: Channel avatar, name, subscriber count

### 🎯 Interactive Features
- **Action Buttons**: Like, Dislike, Share, Save
- **Subscribe Button**: Channel subscription functionality
- **Search Bar**: Video search interface
- **Navigation Menu**: Home, Trending, Categories, About

### 📢 Advertisement Spaces
- **Header Ad**: Top banner advertisement space
- **Middle Ad**: Content area advertisement
- **Sidebar Ad**: Right sidebar advertisement
- **Footer Ad**: Bottom banner advertisement

### 🔧 Backend Features (PHP)
- **Video API**: RESTful API for video metadata
- **Video Streaming**: Efficient video serving with range requests
- **Search Functionality**: Video search capabilities
- **Statistics**: Video views, likes, and analytics
- **CORS Support**: Cross-origin request handling

## File Structure
```
video-player-site/
├── index.html              # Main HTML file
├── css/
│   └── style.css           # Complete CSS styling
├── js/
│   └── script.js           # JavaScript functionality
├── php/
│   ├── config.php          # PHP configuration
│   ├── api.php             # Video API endpoints
│   └── video.php           # Video streaming handler
├── videos/
│   ├── archita-phukan-viral-video.mp4
│   └── VID_20250709154938.mp4
├── logs/                   # Application logs
├── .htaccess              # Apache configuration
└── README.md              # This documentation
```

## API Endpoints

### GET /api/videos
Get list of all videos with pagination
- Parameters: `page`, `limit`, `category`, `sort`, `order`

### GET /api/videos/{id}
Get specific video details by ID or filename

### GET /api/search
Search videos by query
- Parameters: `q` (search query)

### GET /api/stats
Get application statistics

### GET /api/health
Health check endpoint

### POST /api/view
Record video view
- Body: `{"video_id": "1"}`

### POST /api/like
Record video like/unlike
- Body: `{"video_id": "1", "action": "like"}`

### POST /api/dislike
Record video dislike
- Body: `{"video_id": "1", "action": "dislike"}`

## Video Streaming
Videos are served through `/php/video.php` with support for:
- Range requests for video seeking
- Proper MIME type detection
- Efficient streaming for large files
- Error handling and logging

## Browser Compatibility
- Chrome/Chromium (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Features
- **Lazy Loading**: Thumbnails load as needed
- **Efficient Streaming**: Range request support for videos
- **Caching**: Static asset caching via .htaccess
- **Compression**: Gzip compression for text files
- **Optimized CSS**: Custom properties and efficient selectors

## Security Features
- **CORS Headers**: Proper cross-origin handling
- **File Validation**: Video file type validation
- **Path Security**: Prevents directory traversal
- **Input Sanitization**: Safe handling of user inputs

## Accessibility Features
- **Keyboard Navigation**: Full keyboard support
- **Focus Indicators**: Clear focus states
- **Screen Reader Support**: Semantic HTML structure
- **High Contrast**: Good color contrast ratios

## Mobile Features
- **Touch Support**: Touch-friendly interface
- **Swipe Gestures**: Basic swipe detection
- **Responsive Video**: Adaptive video sizing
- **Mobile Menu**: Collapsible navigation

## Installation & Setup

1. **Requirements**:
   - PHP 7.4+ with CLI support
   - Web server (Apache/Nginx) or PHP built-in server
   - FFmpeg (optional, for video metadata)

2. **Local Development**:
   ```bash
   cd video-player-site
   php -S 0.0.0.0:8080 -t .
   ```

3. **Production Deployment**:
   - Upload files to web server
   - Ensure PHP and required extensions are installed
   - Configure .htaccess for URL rewriting
   - Set proper file permissions

## Browser Testing
The site has been tested and verified to work correctly with:
- ✅ Video playback and controls
- ✅ Responsive design across devices
- ✅ Interactive features (like, share, subscribe)
- ✅ Video switching and playlist functionality
- ✅ Advertisement space placement
- ✅ PHP backend API functionality

## Customization
- **Colors**: Modify CSS custom properties in `:root`
- **Videos**: Add new videos to `/videos/` directory
- **Styling**: Update `css/style.css` for design changes
- **Functionality**: Extend `js/script.js` for new features
- **Backend**: Modify PHP files for additional API endpoints

## License
This project is created for demonstration purposes. Ensure you have proper rights to any video content used.

---

**VideoTube** - Your premier destination for video content. Watch, share, and discover amazing videos with a modern, responsive interface.

