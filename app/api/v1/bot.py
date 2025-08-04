from fastapi import APIRouter, Query, HTTPException
from urllib.parse import quote
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.chrome.options import Options
from os import environ

router = APIRouter()

@router.get("/bot")
def visit(url: str = Query(..., description="URL to be passed to imageproxy")):
    if not url.startswith(("http://", "https://")):
        raise HTTPException(status_code=400, detail="Invalid URL format")

    proxy_url = f"http://localhost:8000/api/v1/imageproxy?url={quote(url)}"

    options = Options()
    options.add_argument("--headless")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")
    options.add_argument("--disable-software-rasterizer")
    options.add_argument("--disable-extensions")
    options.add_argument("--disable-application-cache")

    try:
        driver = webdriver.Chrome(
            service=Service(ChromeDriverManager(driver_version=environ.get("CHROME_VERSION")).install()),
            options=options
        )
        driver.get(proxy_url)
        driver.implicitly_wait(5)
    except Exception as e:
        print(f"Bot failed Message: {e}")
    finally:
        try:
            driver.quit()
        except:
            pass

        return {"message": "Bot visit is completed"}