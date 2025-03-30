<details type="reasoning" done="true" duration="6">
<summary>Thought for 6 seconds</summary>
> Okay, I need to create a professional README.md for the Graveloot plugin based on the user's previous requests. Let me start by understanding what the user wants. They mentioned making the text more human, changing the plugin name to Graveloot, and ensuring it's professional. They also referenced their GitHub name, fadijalal07, who helped them. First, I'll review the previous README structure they liked and incorporate the new name. 
> 
> The user wants a natural, conversational tone but professional. So, avoid overly technical jargon but maintain clarity. They want to acknowledge fadijalal07 properly. The features need to highlight unique aspects like particle effects, holograms, expiration warnings, etc. 
> 
> Structure-wise, include sections like Features, Installation, Configuration, Usage, Credits, License, and Contributing. Make sure the video showcase is prominent. Use badges for PMMP version, license, etc. Since the user mentioned "more paragraph type," ensure descriptions are concise but informative in paragraphs rather than bullet points where possible.
> 
> Check for proper markdown formatting, use emojis to make it visually appealing without overdoing it. Ensure links and placeholders (like email, video name) are correctly placed where the user can customize later. Finally, proofread to ensure it flows smoothly and addresses all user requirements without any markdown syntax errors.
</details>
```markdown
# Graveloot 🌟  
**Smart Death Chests for Modern Minecraft Servers**

[![PocketMine Version](https://img.shields.io/badge/PocketMine-5.0.0+-orange.svg)](https://pmmp.io)  
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)  
*Because nobody likes losing their hard-earned diamonds to a clumsy fall*

<div align="center">
  <video src="graveloot-demo.mp4" width="800" poster="thumbnail.jpg" controls></video>
</div>

---

## 🧠 Why Graveloot?
We've all been there - that heart-stopping moment when you fall into lava with a full inventory. Graveloot takes the sting out of death by creating a temporary storage chest where your items are safely preserved. It's like having a friendly ghost that packs up your belongings until you can retrieve them!

---

## 🚀 Features That Matter
**Smart Chest Spawning**  
Graveloot automatically creates single or double chests based on how much loot you dropped. Forget item explosions - your gear stays neatly organized.

**Never Miss a Chest**  
A glowing particle effect guides you to your items, complete with floating text showing your name and a countdown timer. It's like GPS for your afterlife!

**Server-Friendly Design**  
Chests auto-remove after 5 minutes (configurable) with warnings at 2, 1, 0.5, and 0.25 minute marks. We prevent chunk leaks and keep your world clean.

---

## ⚡ Quick Start
1. **Install**  
   Drop the `Graveloot.phar` into your `plugins/` folder
2. **Restart**  
   Let your server breathe new life
3. **Die** *(the fun part)*  
   Test with `/kill` or creative dying
4. **Follow**  
   Chase the smoke signals to your loot

---

## 🔧 Configuration Made Simple
Tweak settings in `plugins/Graveloot/config.yml`:

```yaml
# How long chests stay (seconds)
chest-duration: 300

# Visual feedback options
enable-particles: true    # Toggle smoke guides
particle-count: 20       # More sparkles!

# Custom messages
messages:
  death-message: "🔔 Your loot's waiting at X: %x Y: %y Z: %z"
  time-remaining: "⏳ Gone in: %02d:%02d"
  warning: "⚠️ %s seconds remaining!"
  removed: "❌ Chest vanished"
  loot-click: "✅ Items recovered!"
```

---

## 🛠️ Behind the Scenes
**For Server Owners**  
- Chests survive explosions (no more creeper oopsies!)
- Automatic cleanup even if chunks unload
- Lightweight - doesn't slow down your ticks

**Developers Will Appreciate**  
- Clean event-driven architecture
- No database dependency
- Full API documentation coming in v2

---

## 🤝 Credits & Thanks
This plugin wouldn't exist without:  
- **[fadijalal07](https://github.com/fadijalal07)** - For breakthrough troubleshooting on hologram persistence  
- PMMP Community - Keeping bedrock modding alive  
- Coffee - The real MVP

---

## ❓ Need Help? Found a Bug?
[Open an issue](https://github.com/chirag-gamer/GraveLoot/issues) • [Email Support](mailto:your@email.com)

```text
MIT License © 2024 chiraggamer
*Share, modify, enjoy - just credit the original*
```

---

**Love Graveloot?**  
⭐ Star the repo • 👷♂️ Contribute code • 📣 Tell server friends
```

