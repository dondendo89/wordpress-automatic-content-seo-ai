// Demo JavaScript for WP Gemini Content Generator Live Preview

document.addEventListener('DOMContentLoaded', function() {
    // Initialize demo functionality
    initTabs();
    initDemo();
    initAnimations();
});

// Tab functionality
function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            btn.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
}

// Demo functionality
function initDemo() {
    const generateBtn = document.getElementById('generate-btn');
    const titleInput = document.getElementById('demo-title');
    
    generateBtn.addEventListener('click', function() {
        const title = titleInput.value.trim();
        
        if (!title) {
            showNotification('Please enter a post title', 'error');
            return;
        }
        
        // Simulate AI generation
        simulateGeneration(title);
    });
    
    // Allow Enter key to trigger generation
    titleInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            generateBtn.click();
        }
    });
}

// Simulate AI content generation
function simulateGeneration(title) {
    const generateBtn = document.getElementById('generate-btn');
    const originalText = generateBtn.innerHTML;
    
    // Show loading state
    generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    generateBtn.disabled = true;
    
    // Simulate API delay
    setTimeout(() => {
        // Update content based on title
        updateGeneratedContent(title);
        
        // Reset button
        generateBtn.innerHTML = originalText;
        generateBtn.disabled = false;
        
        // Show success notification
        showNotification('Content generated successfully!', 'success');
        
        // Switch to content tab
        document.querySelector('[data-tab="content"]').click();
        
    }, 2000);
}

// Update generated content based on title
function updateGeneratedContent(title) {
    const contentPreview = document.querySelector('#content-tab .content-preview');
    const metaDescription = document.querySelector('#meta-tab .description');
    const tagsPreview = document.querySelector('#tags-tab .tags-preview');
    const excerptPreview = document.querySelector('#excerpt-tab .excerpt-preview');
    
    // Generate content based on title
    const generatedContent = generateContentForTitle(title);
    
    // Update content - ensure proper HTML rendering
    const cleanContent = generatedContent.content
        .replace(/\n\s*/g, '') // Remove extra whitespace and newlines
        .replace(/>\s+</g, '><') // Remove whitespace between tags
        .trim();
    
    // Debug: Log the content being inserted
    console.log('Generated content:', cleanContent);
    
    contentPreview.innerHTML = cleanContent;
    
    // Update meta description
    metaDescription.textContent = generatedContent.metaDescription;
    
    // Update tags - generate dynamic tags based on title
    const dynamicTags = generateDynamicTags(title);
    tagsPreview.innerHTML = dynamicTags.map(tag => 
        `<span class="tag">${tag}</span>`
    ).join('');
    
    // Update excerpt
    excerptPreview.innerHTML = `<p>${generatedContent.excerpt}</p>`;
}

// Generate dynamic tags based on title
function generateDynamicTags(title) {
    const lowerTitle = title.toLowerCase();
    const words = title.split(' ').filter(word => word.length > 2);
    
    // Base tags from title words
    let tags = words.map(word => 
        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
    );
    
    // Add contextual tags based on keywords
    if (lowerTitle.includes('wordpress') || lowerTitle.includes('wp')) {
        tags.push('WordPress', 'CMS', 'Website Development');
    }
    if (lowerTitle.includes('seo') || lowerTitle.includes('search')) {
        tags.push('SEO', 'Search Optimization', 'Digital Marketing');
    }
    if (lowerTitle.includes('ecommerce') || lowerTitle.includes('shop')) {
        tags.push('E-commerce', 'Online Store', 'Business');
    }
    if (lowerTitle.includes('plugin') || lowerTitle.includes('extension')) {
        tags.push('Plugin', 'Extension', 'Software');
    }
    if (lowerTitle.includes('auto') || lowerTitle.includes('car')) {
        tags.push('Automotive', 'Car', 'Vehicle', 'Auto Insurance');
    }
    if (lowerTitle.includes('insurance') || lowerTitle.includes('assicurazione')) {
        tags.push('Insurance', 'Coverage', 'Protection');
    }
    if (lowerTitle.includes('genertel')) {
        tags.push('Genertel', 'Insurance Company', 'RC Auto');
    }
    
    // Add generic relevant tags
    tags.push('Technology', 'Digital', 'Online', 'Web');
    
    // Remove duplicates and limit to 8 tags
    const uniqueTags = [...new Set(tags)];
    return uniqueTags.slice(0, 8);
}

// Generate content based on title
function generateContentForTitle(title) {
    const contentTemplates = {
        'wordpress': {
            content: `
                <p>WordPress has revolutionized the way we create and manage websites, offering unparalleled flexibility and ease of use. As the world's most popular content management system, WordPress powers over 40% of all websites on the internet, from simple blogs to complex e-commerce platforms.</p>
                
                <p>The platform's extensive plugin ecosystem allows users to add virtually any functionality they need. With thousands of free and premium plugins available, WordPress can be customized to meet the specific requirements of any business or individual. This flexibility makes it an ideal choice for both beginners and experienced developers.</p>
                
                <p>WordPress's open-source nature means it's constantly being improved by a global community of developers. Regular updates ensure security, performance, and feature enhancements. The platform's commitment to accessibility and web standards makes it a reliable choice for creating websites that work across all devices and browsers.</p>
                
                <p>Whether you're building a personal blog, a corporate website, or an online store, WordPress provides the tools and flexibility needed to create a professional, functional website. Its user-friendly interface and extensive documentation make it accessible to users of all skill levels.</p>
            `,
            metaDescription: `Discover everything about WordPress - the world's most popular CMS. Learn about plugins, themes, customization, and how to build amazing websites with WordPress.`,
            tags: ['WordPress', 'CMS', 'Website Development', 'Blogging', 'Web Design', 'Content Management', 'Open Source', 'PHP'],
            excerpt: `WordPress is the world's most popular content management system, powering over 40% of websites. Learn about its features, plugins, and how to build amazing websites.`
        },
        'seo': {
            content: `
                <p>Search Engine Optimization (SEO) is crucial for any website's success in today's competitive digital landscape. SEO involves optimizing your website's content, structure, and technical elements to improve its visibility in search engine results pages (SERPs).</p>
                
                <p>Effective SEO strategies include keyword research, on-page optimization, technical SEO, and link building. By implementing these techniques, websites can attract more organic traffic, increase brand visibility, and ultimately drive more conversions and sales.</p>
                
                <p>Modern SEO goes beyond simple keyword stuffing. Search engines now prioritize user experience, page speed, mobile responsiveness, and high-quality, relevant content. Websites that provide value to users while meeting technical SEO requirements are more likely to rank higher in search results.</p>
                
                <p>Regular SEO audits and monitoring are essential for maintaining and improving search rankings. Tools like Google Analytics, Search Console, and various SEO plugins help track performance and identify areas for improvement. Consistent effort and adaptation to algorithm changes are key to long-term SEO success.</p>
            `,
            metaDescription: `Learn essential SEO strategies to improve your website's search rankings. Discover keyword research, on-page optimization, and technical SEO techniques.`,
            tags: ['SEO', 'Search Engine Optimization', 'Digital Marketing', 'Keywords', 'Google Ranking', 'Website Traffic', 'Online Marketing', 'Content Marketing'],
            excerpt: `Master SEO strategies to improve your website's search rankings. Learn about keyword research, on-page optimization, and technical SEO techniques.`
        },
        'genertel': {
            content: `
                <p>RC Auto Genertel rappresenta una delle soluzioni assicurative più complete e affidabili per la protezione del tuo veicolo. Con anni di esperienza nel settore assicurativo, Genertel offre polizze RC Auto personalizzate che si adattano alle esigenze specifiche di ogni automobilista.</p>
                
                <p>La copertura RC Auto di Genertel garantisce la protezione legale obbligatoria per danni a terzi, ma va ben oltre offrendo servizi aggiuntivi come assistenza stradale 24/7, carro attrezzi, auto sostitutiva e molto altro. Questi servizi extra rendono la polizza Genertel una scelta intelligente per chi cerca sicurezza e tranquillità.</p>
                
                <p>Con Genertel, puoi contare su un servizio clienti dedicato e su processi digitali innovativi che semplificano la gestione della tua polizza. La compagnia investe costantemente in tecnologia per offrire un'esperienza sempre più efficiente e trasparente ai propri clienti.</p>
                
                <p>Scegliere RC Auto Genertel significa affidarsi a una compagnia solida e affidabile, con una rete di agenti e consulenti pronti ad assisterti in ogni momento. La trasparenza nelle condizioni contrattuali e la rapidità nei risarcimenti sono i punti di forza che distinguono Genertel nel panorama assicurativo italiano.</p>
            `,
            metaDescription: `Scopri RC Auto Genertel: la polizza assicurativa completa per la tua auto. Copertura obbligatoria, assistenza 24/7 e servizi aggiuntivi per la tua sicurezza.`,
            tags: ['RC Auto', 'Genertel', 'Assicurazione Auto', 'Copertura Veicoli', 'Assistenza Stradale', 'Polizza Auto', 'Sicurezza', 'Protezione'],
            excerpt: `RC Auto Genertel offre polizze assicurative complete per la tua auto con copertura obbligatoria, assistenza 24/7 e servizi aggiuntivi per la massima sicurezza.`
        },
        'rc auto': {
            content: `
                <p>RC Auto Genertel rappresenta una delle soluzioni assicurative più complete e affidabili per la protezione del tuo veicolo. Con anni di esperienza nel settore assicurativo, Genertel offre polizze RC Auto personalizzate che si adattano alle esigenze specifiche di ogni automobilista.</p>
                
                <p>La copertura RC Auto di Genertel garantisce la protezione legale obbligatoria per danni a terzi, ma va ben oltre offrendo servizi aggiuntivi come assistenza stradale 24/7, carro attrezzi, auto sostitutiva e molto altro. Questi servizi extra rendono la polizza Genertel una scelta intelligente per chi cerca sicurezza e tranquillità.</p>
                
                <p>Con Genertel, puoi contare su un servizio clienti dedicato e su processi digitali innovativi che semplificano la gestione della tua polizza. La compagnia investe costantemente in tecnologia per offrire un'esperienza sempre più efficiente e trasparente ai propri clienti.</p>
                
                <p>Scegliere RC Auto Genertel significa affidarsi a una compagnia solida e affidabile, con una rete di agenti e consulenti pronti ad assisterti in ogni momento. La trasparenza nelle condizioni contrattuali e la rapidità nei risarcimenti sono i punti di forza che distinguono Genertel nel panorama assicurativo italiano.</p>
            `,
            metaDescription: `Scopri RC Auto Genertel: la polizza assicurativa completa per la tua auto. Copertura obbligatoria, assistenza 24/7 e servizi aggiuntivi per la tua sicurezza.`,
            tags: ['RC Auto', 'Genertel', 'Assicurazione Auto', 'Copertura Veicoli', 'Assistenza Stradale', 'Polizza Auto', 'Sicurezza', 'Protezione'],
            excerpt: `RC Auto Genertel offre polizze assicurative complete per la tua auto con copertura obbligatoria, assistenza 24/7 e servizi aggiuntivi per la massima sicurezza.`
        },
        'ecommerce': {
            content: `
                <p>E-commerce has transformed the way businesses sell products and services online, creating new opportunities for entrepreneurs and established companies alike. With the right strategies and tools, any business can successfully establish an online presence and reach customers worldwide.</p>
                
                <p>Successful e-commerce businesses focus on user experience, mobile optimization, and seamless checkout processes. Platforms like WooCommerce, Shopify, and Magento provide robust solutions for building and managing online stores, each offering unique features and capabilities.</p>
                
                <p>Digital marketing plays a crucial role in e-commerce success. Social media marketing, email campaigns, and search engine optimization help drive traffic and convert visitors into customers. Understanding your target audience and their shopping behaviors is essential for creating effective marketing strategies.</p>
                
                <p>Customer service and support are equally important in the e-commerce world. Providing excellent customer experience, including fast shipping, easy returns, and responsive support, builds trust and encourages repeat business. Building a strong brand reputation online is crucial for long-term success.</p>
            `,
            metaDescription: `Discover e-commerce strategies and tools to build a successful online store. Learn about platforms, marketing, and customer experience optimization.`,
            tags: ['E-commerce', 'Online Store', 'WooCommerce', 'Digital Marketing', 'Online Business', 'E-commerce Platform', 'Online Shopping', 'Business Strategy'],
            excerpt: `Build a successful e-commerce business with proven strategies. Learn about platforms, marketing, and customer experience optimization.`
        }
    };
    
    // Default content
    const defaultContent = {
        content: `
            <p>This is a comprehensive guide about "${title}". The content has been generated using advanced AI technology to provide valuable, SEO-optimized information that engages readers and provides real value.</p>
            
            <p>Our AI-powered content generation system analyzes your title and creates relevant, well-structured content that follows best practices for search engine optimization. The generated content includes proper headings, paragraphs, and formatting to ensure maximum readability and engagement.</p>
            
            <p>The system considers various factors including keyword density, readability scores, and user intent to create content that not only ranks well in search engines but also provides genuine value to your audience. This approach ensures that your content serves both SEO purposes and user experience.</p>
            
            <p>With our advanced AI technology, you can generate high-quality content quickly and efficiently, saving time while maintaining the quality and relevance that your audience expects. The generated content is designed to be informative, engaging, and optimized for search engines.</p>
        `,
        metaDescription: `Learn about ${title} with our comprehensive guide. Discover insights, tips, and strategies to help you succeed.`,
        tags: title.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()),
        excerpt: `Discover everything you need to know about ${title}. Our comprehensive guide provides valuable insights and practical tips.`
    };
    
    // Check for specific keywords
    const lowerTitle = title.toLowerCase();
    for (const [keyword, template] of Object.entries(contentTemplates)) {
        if (lowerTitle.includes(keyword)) {
            return template;
        }
    }
    
    return defaultContent;
}

// Show notifications
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Initialize animations
function initAnimations() {
    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-content i {
            font-size: 1.2rem;
        }
    `;
    document.head.appendChild(style);
    
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animateElements = document.querySelectorAll('.feature-card, .screenshot-item, .compat-item');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Add loading animation for buttons
document.querySelectorAll('.btn-primary, .btn-secondary').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (this.textContent.includes('Download')) {
            e.preventDefault();
            showNotification('This is a demo. Download from CodeCanyon!', 'info');
        }
    });
});

// Console welcome message
console.log('%c🚀 WP Gemini Content Generator Demo', 'color: #667eea; font-size: 20px; font-weight: bold;');
console.log('%cWelcome to the live preview! This demo showcases the plugin\'s capabilities.', 'color: #2c3e50; font-size: 14px;');
console.log('%cTry generating content with different titles to see the AI in action!', 'color: #27ae60; font-size: 12px;');
