const { useEffect, useState } = React;

const navItems = [
    { id: "hero", label: "Главная" },
    { id: "menu", label: "Меню" },
    { id: "experience", label: "О нас" },
    { id: "gallery", label: "Атмосфера" },
    { id: "reviews", label: "Отзывы" },
    { id: "contacts", label: "Контакты" }
];

const menuItems = [
    {
        title: "Капучино Zerno",
        description: "Сливочная текстура, пралине и двойной шот арабики из Йемена.",
        price: "260 ₽",
        tag: "Хит дня"
    },
    {
        title: "Матча латте с тонка",
        description: "Японская матча, фисташковое молоко и воздушный сливочный мусс.",
        price: "320 ₽",
        tag: "Расслабление"
    },
    {
        title: "Эспрессо на кокосовой воде",
        description: "Двойной эспрессо с лёгкой сладостью кокоса и льдом из цветочного чая.",
        price: "280 ₽",
        tag: "Энергия"
    },
    {
        title: "Раф солёная карамель",
        description: "Ванильный раф с карамелью, морской солью и густой кремовой текстурой.",
        price: "310 ₽",
        tag: "Новинка"
    },
    {
        title: "Цитрусовый фильтр",
        description: "Аэропресса с бергамотом, клементином и мёдово-лавровым сиропом.",
        price: "240 ₽",
        tag: "Фильтр"
    },
    {
        title: "Черничный флэт уайт",
        description: "Двойной шот, лесные ягоды, сливки и фирменная воздушная пена.",
        price: "295 ₽",
        tag: "Сезон"
    }
];

const signatureCards = [
    {
        title: "Авторский десерт «Шафран»",
        price: "420 ₽",
        description: "Миндальный бисквит, шафрановый крем, ананас и облепиховый соус.",
        image: "https://images.unsplash.com/photo-1457460866886-40ef8d4b42a0?auto=format&fit=crop&w=900&q=80"
    },
    {
        title: "Сет «Полдник бариста»",
        price: "590 ₽",
        description: "Три сорта кофе, трюфель из халвы и меренга с лавандой.",
        image: "https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80"
    },
    {
        title: "Боул «Баланс»",
        price: "370 ₽",
        description: "Киноа, запечённая тыква, страчателла, орехи и соус мисо.",
        image: "https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=900&q=80"
    }
];

const timelineData = [
    {
        time: "08:00 — 11:30",
        text: "Утренние завтраки: круассаны, каши на кокосовом молоке и свежие соки."
    },
    {
        time: "12:00 — 16:00",
        text: "Деловые ланчи: крем-суп из томатов, боулы и домашние лимонады."
    },
    {
        time: "17:00 — 21:00",
        text: "Вечерние дегустации с живой музыкой и коктейлями на основе колд-брю."
    }
];

const galleryImages = [
    "https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=900&q=80",
    "https://images.unsplash.com/photo-1507133750040-4a8f57021571?auto=format&fit=crop&w=900&q=80",
    "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80",
    "https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=900&q=80",
    "https://images.unsplash.com/photo-1504753793650-d4a2b783c15e?auto=format&fit=crop&w=900&q=80",
    "https://images.unsplash.com/photo-1481833761820-0509d3217039?auto=format&fit=crop&w=900&q=80"
];

const testimonials = [
    {
        name: "Алиса",
        text: "Здесь идеально подают капучино и всегда выбирают музыку под настроение."
    },
    {
        name: "Михаил",
        text: "Бариста рассказывают о каждом зерне так вдохновенно, что хочется забирать домой пачку."
    },
    {
        name: "София",
        text: "Люблю десерт «Шафран» и мягкий свет в зале — особенно зимой."
    }
];

const contactInfo = [
    {
        title: "Локация",
        primary: "Москва, ул. Петровка, 18",
        secondary: "Вход со двора, второй этаж"
    },
    {
        title: "Время работы",
        primary: "Пн-Пт 08:00 — 22:00",
        secondary: "Сб-Вс 09:00 — 23:00"
    },
    {
        title: "Контакты",
        primary: "+7 (999) 777-08-80",
        secondary: "WhatsApp и Telegram для брони"
    }
];

const App = () => {
    const [activeSection, setActiveSection] = useState("hero");

    useEffect(() => {
        const revealElements = document.querySelectorAll(".reveal");
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("visible");
                }
            });
        }, { threshold: 0.18, rootMargin: "0px 0px -40px 0px" });
        revealElements.forEach(el => observer.observe(el));
        return () => revealElements.forEach(el => observer.unobserve(el));
    }, []);

    useEffect(() => {
        const sections = document.querySelectorAll("[data-section]");
        const handleScroll = () => {
            let current = "hero";
            sections.forEach(section => {
                const rect = section.getBoundingClientRect();
                if (rect.top <= window.innerHeight * 0.35 && rect.bottom >= window.innerHeight * 0.35) {
                    current = section.dataset.section;
                }
            });
            setActiveSection(current);
        };
        handleScroll();
        window.addEventListener("scroll", handleScroll);
        return () => window.removeEventListener("scroll", handleScroll);
    }, []);

    const scrollToSection = id => {
        const node = document.getElementById(id);
        if (node) {
            node.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    };

    return (
        <div className="page">
            <header>
                <nav className="navbar">
                    <div className="logo"><img src="logo-zerno.png" alt="Zerno Café логотип" className="logo-img" /></div>
                    <div className="nav-links">
                        {navItems.map(item => (
                            <a
                                key={item.id}
                                href={`#${item.id}`}
                                className={activeSection === item.id ? "active" : ""}
                                onClick={event => {
                                    event.preventDefault();
                                    scrollToSection(item.id);
                                }}
                            >
                                {item.label}
                            </a>
                        ))}
                    </div>
                    <div className="nav-actions">
                        <div className="nav-circle">24/7</div>
                        <button className="nav-btn" onClick={() => scrollToSection("contacts")}>Забронировать стол</button>
                    </div>
                </nav>
            </header>
            <main>
                <section id="hero" data-section="hero">
                    <div className="hero">
                        <div className="hero-intro">
                            <span className="hero-badge">Новое меню ароматов</span>
                            <h1>Больше чем просто кофе</h1>
                            <p>Каждый глоток в Zerno Café — это путешествие. Мы обжариваем зёрна партиями, раскрываем десятки оттенков вкуса и создаём атмосферу, к которой хочется возвращаться.</p>
                            <div className="hero-actions">
                                <button className="primary-btn" onClick={() => scrollToSection("menu")}>Открыть меню</button>
                                <button className="secondary-btn" onClick={() => scrollToSection("experience")}>Узнать о кофейне</button>
                            </div>
                            <div className="hero-stats">
                                <div className="stat-card">
                                    <strong>30+</strong>
                                    сортов спешиэлти кофе
                                </div>
                                <div className="stat-card">
                                    <strong>12</strong>
                                    авторских десертов
                                </div>
                                <div className="stat-card">
                                    <strong>7</strong>
                                    бариста чемпионов
                                </div>
                            </div>
                        </div>
                        <div className="hero-visual">
                            <div className="hero-image">
                                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRuGazZZY9Ae8yAew2Sp87t3wxr5Z2EabbXCkTJn4tz_b-zGz8rlsygTViddBivPWI2oiA&usqp=CAU" alt="Интерьер кофейни" />
                            </div>
                            <div className="hero-stats">
                                <div className="stat-card">
                                    <strong>28°C</strong>
                                    идеальная температура обжарки
                                </div>
                                <div className="stat-card">
                                    <strong>92</strong>
                                    степени помола для дегустаций
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section id="menu" data-section="menu" className="reveal">
                    <h2 className="section-title">Меню Zerno Café</h2>
                    <p className="section-subtitle">Мы соединяем спешиэлти-кофе, ремесленные напитки и гастрономию, чтобы каждый гость нашёл своё настроение в чашке.</p>
                    <div className="menu-grid">
                        {menuItems.map((item, index) => (
                            <div className="menu-card" style={{ animationDelay: `${0.1 * index}s` }} key={item.title}>
                                <div className="menu-meta">
                                    <span>{item.tag}</span>
                                    <span className="menu-price">{item.price}</span>
                                </div>
                                <h3>{item.title}</h3>
                                <p>{item.description}</p>
                            </div>
                        ))}
                    </div>
                    <div className="signatures">
                        {signatureCards.map(card => (
                            <div
                                key={card.title}
                                className="signature-card"
                                style={{ backgroundImage: `url(${card.image})` }}
                            >
                                <div className="signature-content">
                                    <h4>{card.title}</h4>
                                    <span>{card.price}</span>
                                    <p>{card.description}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
                <section id="experience" data-section="experience" className="reveal">
                    <h2 className="section-title">Опыт Zerno</h2>
                    <p className="section-subtitle">Свет, музыка и ароматы выстроены так, чтобы замедлить ритм города и подарить время для общения.</p>
                    <div className="timeline">
                        {timelineData.map(item => (
                            <div className="timeline-item" key={item.time}>
                                <strong>{item.time}</strong>
                                <p>{item.text}</p>
                            </div>
                        ))}
                    </div>
                </section>
                <section id="gallery" data-section="gallery" className="reveal">
                    <h2 className="section-title">Атмосфера и дизайн</h2>
                    <p className="section-subtitle">Лампы ручной работы, живые растения, винил и авторская керамика создают пространство для вдохновения.</p>
                    <div className="gallery-grid">
                        {galleryImages.map((src, index) => (
                            <div
                                key={src}
                                className="gallery-card"
                                style={{ backgroundImage: `url(${src})`, animationDelay: `${index * 0.4}s` }}
                            />
                        ))}
                    </div>
                </section>
                <section id="reviews" data-section="reviews" className="reveal">
                    <h2 className="section-title">Отзывы гостей</h2>
                    <p className="section-subtitle">Вы вдохновляете нас создавать новые сочетания вкусов и делиться любимыми рецептами.</p>
                    <div className="testimonials">
                        <div className="testimonials-track">
                            {testimonials.map((item, index) => (
                                <div className="testimonial-card" style={{ animationDelay: `${index * 0.6}s` }} key={item.name}>
                                    <strong>{item.name}</strong>
                                    <p>{item.text}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
                <section id="contacts" data-section="contacts" className="reveal">
                    <h2 className="section-title">Свяжитесь с нами</h2>
                    <p className="section-subtitle">Бронируйте столик, задавайте вопросы или заказывайте напитки с собой. Мы всегда рядом.</p>
                    <div className="contact-grid">
                        {contactInfo.map(item => (
                            <div className="contact-card" key={item.title}>
                                <strong>{item.title}</strong>
                                <span>{item.primary}</span>
                                <p>{item.secondary}</p>
                            </div>
                        ))}
                    </div>
                </section>
                <section id="cta" className="reveal">
                    <div className="testimonials">
                        <h2 className="section-title">Встретимся в Zerno Café</h2>
                        <p className="section-subtitle">Выбирайте столик и бронируйте заранее, чтобы мы приготовили любимые напитки точно к вашему приходу.</p>
                        <div className="hero-actions" style={{ justifyContent: "center", marginTop: "24px" }}>
                            <button className="primary-btn" onClick={() => window.open("https://calendly.com/?locale=ru", "_blank")}>Онлайн-бронирование</button>
                            <button className="secondary-btn" onClick={() => scrollToSection("hero")}>Вернуться к началу</button>
                        </div>
                    </div>
                </section>
                <div className="floating-orb orb-a" />
                <div className="floating-orb orb-b" />
            </main>
            <footer className="footer">
                © {new Date().getFullYear()} Zerno Café. Вкус, атмосфера и вдохновение каждый день.
            </footer>
        </div>
    );
};

ReactDOM.createRoot(document.getElementById("root")).render(<App />);


