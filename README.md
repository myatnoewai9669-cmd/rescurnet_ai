# 🛡️ RescuerNet AI
### ASEAN Disaster Early Warning & Emergency Response System

> **AI Youth Festa 2026** — Korea-ASEAN AI Development & Startup Competition  
> Organized by NIPA | Supported by ASEAN-Korea Cooperation Fund (AKCF)  
> **SDG Goal 11**: Sustainable Cities and Communities

---

## 🌊 Problem Statement

ASEAN is one of the world's most disaster-prone regions. Myanmar, Philippines, Vietnam, Indonesia, and neighboring countries face devastating floods, typhoons, and landslides every year — displacing millions of people and causing billions in economic losses.

**In 2026 alone**, Myanmar's Sagaing and Ayeyarwady regions experienced severe monsoon flooding affecting over 250,000 people. The Philippines faced multiple typhoons threatening coastal communities of 500,000+.

**The core problem:** Disaster response is reactive, not proactive. Communities receive warnings too late. Evacuation routes are unknown. Shelter locations are unclear.

---

## 💡 Solution — RescuerNet AI

RescuerNet AI is an **AI-powered disaster early warning and emergency response platform** built specifically for ASEAN communities. It combines satellite weather data, AI risk prediction, and real-time mapping to:

- **Predict** flood and typhoon risks before they happen
- **Map** safe evacuation routes automatically
- **Alert** communities with color-coded warning levels
- **Connect** evacuees to nearest open shelter centers

---

## ✨ Key Features

### 🧠 AI Risk Prediction Engine
- Powered by **Hugging Face Mixtral-8x7B** model
- Analyzes rainfall, wind speed, humidity, temperature, and river levels
- Generates risk score (0-100) with confidence percentage
- Provides actionable recommendations for disaster responders
- Rule-based fallback system ensures 24/7 availability

### 🗺️ ASEAN Live Disaster Map
- Interactive **Leaflet.js** map covering all 10 ASEAN nations
- Real-time disaster markers with severity color coding
- Evacuation route visualization (open/congested/closed)
- Evacuation center locations with capacity tracking
- Multiple map styles: Street, Satellite, Dark mode

### 🚨 Multi-Level Alert System
- 4-tier alert levels: 🟢 Green → 🟡 Yellow → 🟠 Orange → 🔴 Red
- Critical alert banner for immediate life-threatening situations
- Population-at-risk tracking per disaster zone
- Alert expiry management

### 🌤️ Real-Time Weather Integration
- **OpenWeatherMap API** integration for live ASEAN weather data
- Monitors: Yangon, Mandalay, Manila, Bangkok, Hanoi, Jakarta
- Tracks rainfall, wind speed, humidity, temperature
- Weather data feeds into AI prediction engine

### 🏠 Evacuation Center Management
- Real-time capacity and occupancy tracking
- Geographic mapping of all shelter locations
- Contact information for emergency coordination
- Status monitoring: Open / Full / Closed

### 🛣️ Evacuation Route Planner
- Route status tracking: Open / Congested / Closed
- Distance and estimated travel time
- Interactive map visualization with route lines
- Linked to specific disaster events

---

## 🎯 SDG Alignment

| SDG Goal | How RescuerNet AI Contributes |
|---|---|
| **Goal 11** — Sustainable Cities | Builds disaster-resilient communities across ASEAN |
| **Goal 13** — Climate Action | AI-powered climate disaster early warning |
| **Goal 17** — Partnerships | Cross-border ASEAN disaster data sharing |

---

## 🛠️ Tech Stack

```
Frontend:     HTML5, CSS3, JavaScript, Leaflet.js
Backend:      PHP 8.x
Database:     MySQL
AI Engine:    Hugging Face Inference API (Mixtral-8x7B-Instruct)
Weather API:  OpenWeatherMap API
Maps:         Leaflet.js + OpenStreetMap + CartoDB Dark
Hosting:      InfinityFree (Live Demo)
Local Dev:    XAMPP
```

---

## 📁 Project Structure

```
rescuernet-ai/
├── index.php              # Main Dashboard
├── map.php                # Live ASEAN Disaster Map
├── prediction.php         # AI Risk Prediction Engine
├── routes.php             # Evacuation Routes Manager
├── centers.php            # Evacuation Centers Tracker
├── config/
│   └── db.php             # Database Configuration
├── api/
│   └── weather.php        # OpenWeatherMap API Handler
├── assets/
│   ├── css/
│   │   ├── style.css      # Main Dark Theme Stylesheet
│   │   ├── map.css        # Map Page Styles
│   │   └── prediction.css # AI Prediction Page Styles
│   └── js/
│       └── map.js         # Map JavaScript
└── database/
    └── rescuernet.sql     # Database Schema + Sample Data
```

---

## 🗄️ Database Schema

| Table | Description |
|---|---|
| `disasters` | Active disaster events with coordinates & severity |
| `alerts` | Multi-level alerts (green/yellow/orange/red) |
| `evacuation_routes` | Safe evacuation paths with status |
| `evacuation_centers` | Shelter locations with capacity tracking |
| `ai_predictions` | AI risk analysis history |
| `weather_logs` | Weather sensor data logs |
| `users` | Admin and responder accounts |

---

## 🚀 Installation & Setup

### Prerequisites
- XAMPP (PHP 8.x + MySQL)
- OpenWeatherMap API Key (free at openweathermap.org)
- Hugging Face API Token (free at huggingface.co)

### Local Setup

```bash
# 1. Clone repository
git clone https://github.com/myatnoewai9669-cmd/rescuernet-ai.git

# 2. Copy to XAMPP htdocs
cp -r rescuernet-ai/ C:/xampp/htdocs/

# 3. Start XAMPP (Apache + MySQL)

# 4. Import database
# phpMyAdmin → Create DB "rescuernet_db" → Import rescuernet.sql

# 5. Configure database
# Edit config/db.php with your credentials

# 6. Add API keys
# prediction.php → $hf_token = 'YOUR_HF_TOKEN'
# index.php → $api_key = 'YOUR_OPENWEATHER_KEY'

# 7. Open browser
# http://localhost/rescuernet-ai/
```

### Demo Credentials
Live Demo:  https://rescuernet.infinityfreeapp.com (or)
rescuernet.infinityfreeapp.com
```

---

## 📸 Screenshots

### Dashboard — Live Disaster Overview
- Real-time stats: Active disasters, alerts, open centers, population at risk
- ASEAN live map with color-coded disaster markers
- Active alert feed with severity levels
- AI risk prediction cards

### AI Prediction Engine
- Weather parameter input with interactive sliders
- AI-generated risk score with SVG gauge visualization
- Confidence percentage and detailed reasoning
- Actionable recommendations for emergency responders

### Live Map
- Full-screen interactive ASEAN map
- Layer toggles: Disasters / Centers / Routes
- Click markers for detailed popup information
- Multiple tile styles (Street/Satellite/Dark)

---

## 🌏 ASEAN Coverage

| Country | Monitored Cities/Regions |
|---|---|
| 🇲🇲 Myanmar | Yangon, Mandalay, Sagaing, Ayeyarwady |
| 🇵🇭 Philippines | Manila, Cagayan Valley, Batanes |
| 🇻🇳 Vietnam | Hanoi, Ho Chi Minh City |
| 🇹🇭 Thailand | Bangkok, Chiang Mai |
| 🇮🇩 Indonesia | Jakarta, Sumatra |
| 🇱🇦 Laos | Vientiane, Champasak |
| 🇰🇭 Cambodia | Phnom Penh |
| 🇲🇾 Malaysia | Kuala Lumpur |

---

## 💰 Impact & Potential

- **830,000+** people currently at risk in monitored zones
- **3** active evacuation centers with **18,000** total capacity
- **10** ASEAN nations covered
- Early warning can reduce disaster mortality by up to **40%** (UNDRR)

---

## 👩‍💻 Developer

**Myat Noe Wai**  
Information Systems Student — Nusa Putra University, Indonesia  
Originally from Myanmar 🇲🇲 — Motivated by real flood disasters affecting communities

- GitHub: [github.com/myatnoewai9669-cmd](https://github.com/myatnoewai9669-cmd)
- LinkedIn: [linkedin.com/in/myat-noe-wai-10a6b3406](https://linkedin.com/in/myat-noe-wai-10a6b3406)

---

## 📋 Competition Info

| Item | Details |
|---|---|
| Competition | AI Youth Festa 2026 |
| Organizer | NIPA (National IT Industry Promotion Agency) |
| Supporter | ASEAN-Korea Cooperation Fund (AKCF) |
| Application Period | 28 July – 21 August 2026, 16:00 PHT |
| Final Location | Philippines |
| Prize Pool | 1st: $20,000 · 2nd: $10,000 · 3rd: $5,000 USD |
| SDG Target | Goal 11 — Sustainable Cities and Communities |

---

## 📄 License

MIT License — Open source for humanitarian use across ASEAN communities.

---

*Built with ❤️ for ASEAN communities affected by natural disasters.*  
*"Technology should save lives — not just make them easier."*
